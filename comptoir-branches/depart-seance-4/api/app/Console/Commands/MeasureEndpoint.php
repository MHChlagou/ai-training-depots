<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Mesure reproductible d'un endpoint GET, dans le processus (sans serveur HTTP).
 *
 *   php artisan perf:measure /api/orders --runs=10 --label=avant
 *   php artisan perf:measure "/api/orders?customer_id=42" --runs=10 --label=avant --explain
 *
 * Affiche : temps (médiane, min, max), nombre de requêtes SQL par appel, temps SQL cumulé,
 * requêtes répétées (signe d'un N+1) et, avec --explain, le plan EXPLAIN ANALYZE de la
 * requête principale. Écrit un rapport JSON dans storage/perf/<label>.json.
 */
class MeasureEndpoint extends Command
{
    protected $signature = 'perf:measure
        {uri : Chemin à mesurer, par exemple /api/orders?customer_id=42}
        {--runs=10 : Nombre d\'appels mesurés (un appel d\'échauffement est ajouté et ignoré)}
        {--label=mesure : Nom du rapport JSON écrit dans storage/perf/}
        {--token= : Jeton Bearer si la route est protégée}
        {--explain : Lance EXPLAIN ANALYZE sur la première requête SQL qui lit la table ciblée}
        {--table=orders : Table visée par --explain}';

    protected $description = 'Mesure un endpoint GET : temps, nombre de requêtes SQL, requêtes répétées, EXPLAIN';

    public function handle(HttpKernel $kernel): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Commande réservée aux environnements de développement et de test.');

            return self::FAILURE;
        }

        $uri = (string) $this->argument('uri');
        $runs = max(1, (int) $this->option('runs'));

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries) {
            $queries[] = ['sql' => $query->sql, 'bindings' => $query->bindings, 'time_ms' => $query->time];
        });

        $durations = [];
        $perRun = [];
        $status = null;

        for ($i = 0; $i <= $runs; $i++) {
            $queries = [];
            $request = Request::create($uri, 'GET');
            $request->headers->set('Accept', 'application/json');
            if ($this->option('token')) {
                $request->headers->set('Authorization', 'Bearer '.$this->option('token'));
            }

            $start = hrtime(true);
            $response = $kernel->handle($request);
            $elapsedMs = (hrtime(true) - $start) / 1e6;
            $kernel->terminate($request, $response);

            $status = $response->getStatusCode();
            if ($i === 0) {
                continue; // appel d'échauffement : autoload, cache de config, connexion SQL
            }
            $durations[] = $elapsedMs;
            $perRun = $queries; // on garde le détail du dernier appel
        }

        if ($status >= 400) {
            $this->error("Réponse HTTP {$status} : vérifiez l'URI, le jeton ou les données.");

            return self::FAILURE;
        }

        sort($durations);
        $median = $durations[intdiv(count($durations), 2)];
        $sqlTime = array_sum(array_column($perRun, 'time_ms'));

        // Regroupe les requêtes identiques (au paramètre près) : une même requête répétée N fois = N+1 probable.
        $repeated = collect($perRun)
            ->groupBy('sql')
            ->map(fn ($group, $sql) => ['sql' => $sql, 'count' => $group->count()])
            ->filter(fn ($row) => $row['count'] > 1)
            ->sortByDesc('count')
            ->values()
            ->all();

        $report = [
            'label' => $this->option('label'),
            'uri' => $uri,
            'runs' => $runs,
            'http_status' => $status,
            'time_ms' => [
                'median' => round($median, 1),
                'min' => round($durations[0], 1),
                'max' => round(end($durations), 1),
            ],
            'queries_per_request' => count($perRun),
            'sql_time_ms' => round($sqlTime, 1),
            'repeated_queries' => $repeated,
            'measured_at' => now()->toIso8601String(),
        ];

        $this->info("Endpoint : GET {$uri}  (HTTP {$status}, {$runs} appels mesurés)");
        $this->table(['Mesure', 'Valeur'], [
            ['Temps médian (ms)', $report['time_ms']['median']],
            ['Temps min / max (ms)', $report['time_ms']['min'].' / '.$report['time_ms']['max']],
            ['Requêtes SQL par appel', $report['queries_per_request']],
            ['Temps SQL cumulé (ms)', $report['sql_time_ms']],
        ]);

        if ($repeated !== []) {
            $this->warn('Requêtes répétées (N+1 probable) :');
            foreach (array_slice($repeated, 0, 5) as $row) {
                $this->line(sprintf('  %4d x  %s', $row['count'], $row['sql']));
            }
        }

        if ($this->option('explain')) {
            $table = (string) $this->option('table');
            $target = collect($perRun)->first(
                fn ($q) => str_starts_with(strtolower($q['sql']), 'select')
                    && str_contains($q['sql'], "from \"{$table}\"")
                    && ! str_contains(strtolower($q['sql']), 'count(')
            );
            if ($target === null) {
                $this->warn("Aucune requête SELECT sur \"{$table}\" trouvée pour EXPLAIN.");
            } else {
                $plan = DB::select('EXPLAIN (ANALYZE, BUFFERS) '.$target['sql'], $target['bindings']);
                $lines = array_map(fn ($row) => array_values((array) $row)[0], $plan);
                $report['explain'] = ['sql' => $target['sql'], 'plan' => $lines];
                $this->newLine();
                $this->info('EXPLAIN ANALYZE : '.$target['sql']);
                foreach ($lines as $line) {
                    $this->line('  '.$line);
                }
            }
        }

        $path = storage_path('perf/'.$this->option('label').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->newLine();
        $this->line("Rapport écrit : {$path}");

        return self::SUCCESS;
    }
}
