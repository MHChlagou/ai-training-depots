import { test, expect } from '@playwright/test';

test('le catalogue affiche des produits et ouvre une fiche produit', async ({ page }) => {
  await page.goto('/catalogue');

  const cards = page.getByTestId('product-card');
  await expect(cards.first()).toBeVisible();

  await cards.first().getByRole('link').first().click();

  await expect(page.getByLabel('Quantité')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Ajouter au panier' })).toBeVisible();
});
