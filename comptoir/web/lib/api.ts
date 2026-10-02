import type {
  CreateOrderPayload,
  Customer,
  Order,
  Product,
  RegisterCustomerPayload,
} from '@/types/api';

const DEFAULT_API_URL = 'http://127.0.0.1:8000';

export type ApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; status: number; message: string; errors: Record<string, string[]> };

type ErrorBody = {
  message?: unknown;
  errors?: unknown;
};

/**
 * URL de base de l'API Laravel.
 * Côté serveur, API_URL est prioritaire ; le navigateur ne connaît que NEXT_PUBLIC_API_URL.
 */
export function apiBaseUrl(): string {
  const url =
    typeof window === 'undefined'
      ? (process.env.API_URL ?? process.env.NEXT_PUBLIC_API_URL ?? DEFAULT_API_URL)
      : (process.env.NEXT_PUBLIC_API_URL ?? DEFAULT_API_URL);

  return url.replace(/\/+$/, '');
}

function apiUrl(path: string): string {
  return `${apiBaseUrl()}${path}`;
}

async function getJson<T>(path: string): Promise<{ status: number; body: T | null }> {
  const response = await fetch(apiUrl(path), {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (response.status === 404) {
    return { status: 404, body: null };
  }

  if (!response.ok) {
    throw new Error(`GET ${path} : réponse HTTP ${response.status}`);
  }

  return { status: response.status, body: (await response.json()) as T };
}

function toErrors(value: unknown): Record<string, string[]> {
  if (typeof value !== 'object' || value === null) {
    return {};
  }

  const errors: Record<string, string[]> = {};
  for (const [field, messages] of Object.entries(value)) {
    if (Array.isArray(messages)) {
      errors[field] = messages.filter((message): message is string => typeof message === 'string');
    }
  }

  return errors;
}

async function postJson<T>(path: string, payload: unknown): Promise<ApiResult<T>> {
  let response: Response;
  try {
    response = await fetch(apiUrl(path), {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      cache: 'no-store',
    });
  } catch {
    return {
      ok: false,
      status: 0,
      message: 'Impossible de joindre le serveur. Réessayez dans un instant.',
      errors: {},
    };
  }

  const body: unknown = await response.json().catch(() => null);

  if (response.ok) {
    return { ok: true, data: (body as { data: T }).data };
  }

  const errorBody = (body ?? {}) as ErrorBody;

  return {
    ok: false,
    status: response.status,
    message:
      typeof errorBody.message === 'string'
        ? errorBody.message
        : `Erreur inattendue (HTTP ${response.status}).`,
    errors: toErrors(errorBody.errors),
  };
}

export async function getProducts(): Promise<Product[]> {
  const { body } = await getJson<{ data: Product[] }>('/api/products');

  return body?.data ?? [];
}

export async function getProduct(slug: string): Promise<Product | null> {
  const { body } = await getJson<{ data: Product }>(`/api/products/${encodeURIComponent(slug)}`);

  return body?.data ?? null;
}

export function createOrder(payload: CreateOrderPayload): Promise<ApiResult<Order>> {
  return postJson<Order>('/api/orders', payload);
}

export function registerCustomer(payload: RegisterCustomerPayload): Promise<ApiResult<Customer>> {
  return postJson<Customer>('/api/register', payload);
}
