// Configuration Playwright de comptoir/web.
// Destination : web/playwright.config.ts
// Installation : npm install -D @playwright/test && npx playwright install chromium
import { defineConfig, devices } from '@playwright/test';

const isCI = !!process.env.CI;
const apiUrl = process.env.E2E_API_URL ?? 'http://127.0.0.1:8000';
const webUrl = process.env.E2E_BASE_URL ?? 'http://localhost:3000';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  // Interdit un test.only oublié en CI.
  forbidOnly: isCI,
  retries: isCI ? 2 : 0,
  workers: isCI ? 1 : undefined,
  reporter: isCI
    ? [['github'], ['html', { open: 'never' }], ['junit', { outputFile: 'test-results/junit.xml' }]]
    : [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: webUrl,
    locale: 'fr-FR',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
  ],
  // Démarre l'API Laravel puis le front Next.js avant les tests.
  // En local, réutilise les serveurs déjà lancés (php artisan serve, npm run dev).
  webServer: [
    {
      command: 'php artisan serve --host=127.0.0.1 --port=8000',
      cwd: '../api',
      url: `${apiUrl}/api/products`,
      reuseExistingServer: !isCI,
      timeout: 120_000,
    },
    {
      command: isCI ? 'npm run build && npm run start' : 'npm run dev',
      url: webUrl,
      reuseExistingServer: !isCI,
      timeout: 180_000,
      // Adaptez le nom de la variable à celui qu'utilise web/ pour joindre l'API.
      env: { NEXT_PUBLIC_API_URL: apiUrl, API_URL: apiUrl },
    },
  ],
});
