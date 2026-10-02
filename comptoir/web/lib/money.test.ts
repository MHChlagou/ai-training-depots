import { describe, expect, it } from 'vitest';
import { formatPrice } from './money';

describe('formatPrice', () => {
  it('formate des centimes en euros à la française', () => {
    // Intl sépare le montant et le symbole par une espace insécable.
    expect(formatPrice(1250)).toBe('12,50 €');
  });
});
