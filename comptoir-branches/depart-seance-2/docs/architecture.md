# Architecture de comptoir

Document produit en séance 1 (exercice 1), relu et corrigé à la main. Destination dans le
dépôt : `docs/architecture.md`. Les schémas sont en Mermaid : ils s'affichent sur GitHub et
GitLab, dans un Artifact Claude.ai ou sur mermaid.live.

## Vue d'ensemble

```mermaid
flowchart LR
    U["Acheteur B2B<br/>(navigateur)"] --> W["web/<br/>Next.js App Router<br/>TypeScript"]
    W -- "HTTP JSON /api/*" --> A["api/<br/>Laravel 12 · PHP 8.3"]
    A --> DB[("PostgreSQL")]

    subgraph WEB["web/ : pages"]
      P1["/ accueil"]
      P2["/catalogue"]
      P3["/produits/[slug]"]
      P4["/panier"]
      P5["/inscription"]
      P6["/commande"]
      P7["/commande/confirmation"]
    end

    subgraph API["api/ : endpoints"]
      E1["GET /api/products"]
      E2["GET /api/products/{slug}"]
      E3["GET /api/orders"]
      E4["POST /api/orders"]
      E5["POST /api/register"]
      E6["GET /api/me (auth:sanctum)"]
    end

    W --- WEB
    A --- API
```

## Modèle de données

```mermaid
erDiagram
    CATEGORY ||--o{ PRODUCT : contient
    CUSTOMER ||--o{ ORDER : passe
    ORDER ||--|{ ORDER_LINE : comprend
    PRODUCT ||--o{ ORDER_LINE : "est commandé dans"
    DISCOUNT |o--o{ ORDER : "code promo"
```

Discount est lié à Order par le code promo : `orders.discount_code` référence `discounts.code`,
sans contrainte de clé étrangère (lien logique, vérifié dans les migrations).

## Flux : consultation des commandes (point fragile)

```mermaid
sequenceDiagram
    participant W as web/ (page commandes)
    participant C as OrderController@index
    participant DB as PostgreSQL
    W->>C: GET /api/orders
    C->>DB: SELECT count(*) FROM orders (pagination, 1 requête)
    C->>DB: SELECT * FROM orders LIMIT 50 (1 requête)
    loop pour chacune des N commandes
        C->>DB: SELECT * FROM customers WHERE id = ? (N requêtes)
        C->>DB: SELECT * FROM order_lines WHERE order_id = ? (N requêtes)
        loop pour chaque ligne de la commande
            C->>DB: SELECT * FROM products WHERE id = ? (1 requête par ligne)
        end
    end
    Note over C,DB: 1 + 1 + 2N + (nombre de lignes) requêtes :<br/>N+1 sur customer, lines et lines.product.<br/>Pas d'index sur orders.customer_id.
    C-->>W: JSON
```

## Flux : création de commande

```mermaid
sequenceDiagram
    participant W as web/ (page commande)
    participant C as OrderController@store
    participant S as OrderService
    participant DB as PostgreSQL
    W->>C: POST /api/orders (client existant ou invité, lignes)
    C->>C: validation (StoreOrderRequest)
    C->>S: place(données validées)
    S->>DB: client existant ou création du client invité
    S->>DB: lecture des produits, vérification du stock
    S->>DB: INSERT orders + order_lines, décrément du stock
    Note over S,DB: total_cents = somme des lignes (HT, sans TVA ni port).<br/>LegacyInvoiceCalculator n'est pas appelé ici.
    S-->>C: commande
    C-->>W: 201 + commande créée
```

## Flux : export de facture

```mermaid
sequenceDiagram
    participant A as Commande Artisan
    participant E as InvoicePdfExporter
    participant I as LegacyInvoiceCalculator
    participant DB as PostgreSQL
    A->>E: php artisan invoices:export {order} puis export(commande)
    E->>DB: client, lignes, produits, code promo
    E->>I: calculate(tableau en euros)
    I->>DB: SELECT discounts (code promo)
    I-->>E: remises, TVA, port, total (lus dans lastResult)
    E-->>A: storage/app/invoices/facture-{id}.pdf
```

## Points fragiles

| Zone | Constat | Risque | Séance où on le traite |
|---|---|---|---|
| `GET /api/orders` | N+1 sur `lines`, `lines.product` et `customer` ; index manquant sur `orders.customer_id` | Temps de réponse qui croît avec le volume | S3 |
| `app/Services/LegacyInvoiceCalculator.php` | ~400 lignes, remises, TVA et arrondis mêlés, aucun test | Régression silencieuse sur les montants | S3, S5 |
| `POST /api/orders` | Montants calculés côté serveur, écriture en base | Incohérence de totaux, validation incomplète | S4, S5 |
| Front `web/` | Pages publiques (catalogue, fiche produit) | SEO et performances | S7 |
