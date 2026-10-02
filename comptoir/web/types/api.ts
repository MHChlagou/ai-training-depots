export type Category = {
  id: number;
  name: string;
  slug: string;
};

export type Product = {
  id: number;
  sku: string;
  slug: string;
  name: string;
  description: string | null;
  price_cents: number;
  stock: number;
  image_url: string | null;
  category: Category;
};

export type CustomerType = 'pro' | 'particulier';

export type Customer = {
  id: number;
  name: string | null;
  email: string;
  company: string | null;
  phone: string | null;
  type: CustomerType;
  country: string;
  created_at: string | null;
};

export type OrderStatus = 'pending' | 'paid' | 'shipped' | 'cancelled';

export type OrderLine = {
  id: number;
  quantity: number;
  unit_price_cents: number;
  product: {
    id: number;
    sku: string;
    name: string;
  };
};

export type Order = {
  id: number;
  reference: string;
  status: OrderStatus;
  placed_at: string | null;
  total_cents: number;
  customer: {
    id: number;
    name: string | null;
    company: string | null;
  };
  lines_count: number;
  lines: OrderLine[];
};

export type Paginated<T> = {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
};

export type OrderLinePayload = {
  product_id: number;
  quantity: number;
};

export type GuestPayload = {
  email: string;
  name: string;
  company: string;
  address: string;
};

export type CreateOrderPayload = {
  customer_id?: number;
  guest?: GuestPayload;
  lines: OrderLinePayload[];
  shipping_address?: string | null;
  discount_code?: string | null;
};

export type RegisterCustomerPayload = {
  name?: string | null;
  company?: string | null;
  email: string;
  password: string;
  password_confirmation: string;
};
