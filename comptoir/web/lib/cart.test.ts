import { describe, expect, it } from 'vitest';
import { computeCartTotal, mergeLine, type CartLine } from './cart';

const tournevis = {
  productId: 1,
  slug: 'tournevis-cruciforme-ph2',
  sku: 'OUT-1',
  name: 'Tournevis cruciforme PH2',
  unitPriceCents: 1999,
};

const niveau = {
  productId: 4,
  slug: 'niveau-a-bulle-60cm',
  sku: 'OUT-4',
  name: 'Niveau à bulle 60 cm',
  unitPriceCents: 1250,
};

describe('computeCartTotal', () => {
  it('additionne prix unitaire x quantité de chaque ligne', () => {
    expect(
      computeCartTotal([
        { unitPriceCents: 2100, quantity: 2 },
        { unitPriceCents: 999, quantity: 1 },
      ]),
    ).toBe(5199);
  });

  it('vaut 0 pour un panier vide', () => {
    expect(computeCartTotal([])).toBe(0);
  });
});

describe('mergeLine', () => {
  it('ajoute une nouvelle ligne pour un produit absent du panier', () => {
    const lines: CartLine[] = [{ ...tournevis, quantity: 1 }];

    expect(mergeLine(lines, niveau, 2)).toEqual([
      { ...tournevis, quantity: 1 },
      { ...niveau, quantity: 2 },
    ]);
  });

  it('additionne les quantités pour un produit déjà présent', () => {
    const lines: CartLine[] = [
      { ...tournevis, quantity: 1 },
      { ...niveau, quantity: 2 },
    ];

    expect(mergeLine(lines, niveau, 3)).toEqual([
      { ...tournevis, quantity: 1 },
      { ...niveau, quantity: 5 },
    ]);
  });

  it('ne modifie pas le panier reçu', () => {
    const lines: CartLine[] = [{ ...tournevis, quantity: 1 }];

    mergeLine(lines, tournevis, 2);

    expect(lines).toEqual([{ ...tournevis, quantity: 1 }]);
  });
});
