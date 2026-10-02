import { describe, expect, it } from 'vitest';
import { isValidEmail } from './validation';

describe('isValidEmail', () => {
  it('accepte une adresse complète', () => {
    expect(isValidEmail('invite@example.com')).toBe(true);
  });

  it('refuse une adresse sans domaine', () => {
    expect(isValidEmail('invite@')).toBe(false);
  });
});
