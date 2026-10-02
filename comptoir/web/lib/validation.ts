const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function isValidEmail(value: string): boolean {
  return EMAIL_PATTERN.test(value.trim());
}

export function isBlank(value: string): boolean {
  return value.trim() === '';
}

export type GuestCheckoutField = 'email' | 'name' | 'company' | 'address';

export type GuestCheckoutValues = Record<GuestCheckoutField, string>;

export const REQUIRED_MESSAGE = 'Ce champ est obligatoire.';
export const INVALID_EMAIL_MESSAGE = 'Adresse e-mail invalide.';

/**
 * Valide le formulaire de commande sans compte.
 * Retourne un message par champ invalide (objet vide si tout est valide).
 */
export function validateGuestCheckout(
  values: GuestCheckoutValues,
): Partial<Record<GuestCheckoutField, string>> {
  const errors: Partial<Record<GuestCheckoutField, string>> = {};

  if (isBlank(values.email)) {
    errors.email = REQUIRED_MESSAGE;
  } else if (!isValidEmail(values.email)) {
    errors.email = INVALID_EMAIL_MESSAGE;
  }

  for (const field of ['name', 'company', 'address'] as const) {
    if (isBlank(values[field])) {
      errors[field] = REQUIRED_MESSAGE;
    }
  }

  return errors;
}
