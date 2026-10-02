'use client';

import { useState, type FormEvent } from 'react';
import { registerCustomer } from '@/lib/api';

type RegisterField = 'name' | 'company' | 'email' | 'password' | 'password_confirmation';

type FieldErrors = Partial<Record<RegisterField, string>>;

const FIELDS: RegisterField[] = ['name', 'company', 'email', 'password', 'password_confirmation'];

function readField(data: FormData, field: RegisterField): string {
  const value = data.get(field);

  return typeof value === 'string' ? value : '';
}

export function RegisterForm() {
  const [errors, setErrors] = useState<FieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [created, setCreated] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setErrors({});
    setFormError(null);
    setCreated(false);

    const name = readField(data, 'name').trim();
    const company = readField(data, 'company').trim();

    const result = await registerCustomer({
      name: name === '' ? null : name,
      company: company === '' ? null : company,
      email: readField(data, 'email').trim(),
      password: readField(data, 'password'),
      password_confirmation: readField(data, 'password_confirmation'),
    });

    if (result.ok) {
      setCreated(true);
      return;
    }

    const apiErrors: FieldErrors = {};
    for (const field of FIELDS) {
      const message = result.errors[field]?.[0];
      if (message) {
        apiErrors[field] = message;
      }
    }
    setErrors(apiErrors);

    if (Object.keys(apiErrors).length === 0) {
      setFormError(result.message);
    }
  }

  function describedBy(field: RegisterField) {
    return errors[field]
      ? { 'aria-invalid': true, 'aria-describedby': `inscription-${field}-erreur` }
      : {};
  }

  function fieldError(field: RegisterField) {
    const error = errors[field];

    return error ? (
      <p id={`inscription-${field}-erreur`} className="field__error">
        {error}
      </p>
    ) : null;
  }

  return (
    <form className="form" onSubmit={handleSubmit}>
      <div className="field">
        <label htmlFor="inscription-name">Nom</label>
        <input
          id="inscription-name"
          name="name"
          type="text"
          autoComplete="name"
          maxLength={120}
          {...describedBy('name')}
        />
        {fieldError('name')}
      </div>
      <div className="field">
        <label htmlFor="inscription-company">Société</label>
        <input
          id="inscription-company"
          name="company"
          type="text"
          autoComplete="organization"
          maxLength={190}
          {...describedBy('company')}
        />
        {fieldError('company')}
      </div>
      <div className="field">
        <label htmlFor="inscription-email">E-mail</label>
        <input
          id="inscription-email"
          name="email"
          type="email"
          autoComplete="email"
          required
          {...describedBy('email')}
        />
        {fieldError('email')}
      </div>
      <div className="field">
        <label htmlFor="inscription-password">Mot de passe</label>
        <input
          id="inscription-password"
          name="password"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
          {...describedBy('password')}
        />
        {fieldError('password')}
      </div>
      <div className="field">
        <label htmlFor="inscription-password-confirmation">Confirmation du mot de passe</label>
        <input
          id="inscription-password-confirmation"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
          {...describedBy('password_confirmation')}
        />
        {fieldError('password_confirmation')}
      </div>
      {formError ? (
        <p role="alert" className="form__error">
          {formError}
        </p>
      ) : null}
      <button type="submit" className="button">
        Créer mon compte
      </button>
      {created ? <p role="status">Votre compte a été créé.</p> : null}
    </form>
  );
}
