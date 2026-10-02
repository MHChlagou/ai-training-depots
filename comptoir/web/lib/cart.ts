export const CART_STORAGE_KEY = 'comptoir.panier';
export const CART_CHANGED_EVENT = 'cart:changed';

export type CartLine = {
  productId: number;
  slug: string;
  sku: string;
  name: string;
  unitPriceCents: number;
  quantity: number;
};

export type CartItem = Omit<CartLine, 'quantity'>;

/**
 * Total HT du panier en centimes.
 */
export function computeCartTotal(
  lines: Array<Pick<CartLine, 'unitPriceCents' | 'quantity'>>,
): number {
  return lines.reduce((total, line) => total + line.unitPriceCents * line.quantity, 0);
}

/**
 * Ajoute un produit au panier ; si le produit y est déjà, les quantités s'additionnent.
 * Ne modifie pas le tableau reçu.
 */
export function mergeLine(lines: CartLine[], item: CartItem, quantity: number): CartLine[] {
  const existing = lines.find((line) => line.productId === item.productId);

  if (!existing) {
    return [...lines, { ...item, quantity }];
  }

  return lines.map((line) =>
    line.productId === item.productId ? { ...line, quantity: line.quantity + quantity } : line,
  );
}

/**
 * Lit un panier sérialisé ; toute valeur illisible donne un panier vide.
 */
export function parseCart(raw: string | null): CartLine[] {
  if (!raw) {
    return [];
  }

  try {
    const value: unknown = JSON.parse(raw);

    return Array.isArray(value) ? (value as CartLine[]) : [];
  } catch {
    return [];
  }
}

function hasWindow(): boolean {
  return typeof window !== 'undefined';
}

export function readCart(): CartLine[] {
  if (!hasWindow()) {
    return [];
  }

  return parseCart(window.localStorage.getItem(CART_STORAGE_KEY));
}

export function writeCart(lines: CartLine[]): void {
  if (!hasWindow()) {
    return;
  }

  window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(lines));
  window.dispatchEvent(new Event(CART_CHANGED_EVENT));
}

export function addToCart(item: CartItem, quantity: number): void {
  if (!hasWindow()) {
    return;
  }

  writeCart(mergeLine(readCart(), item, quantity));
}

export function updateQuantity(productId: number, quantity: number): void {
  if (!hasWindow()) {
    return;
  }

  writeCart(
    readCart().map((line) => (line.productId === productId ? { ...line, quantity } : line)),
  );
}

export function removeFromCart(productId: number): void {
  if (!hasWindow()) {
    return;
  }

  writeCart(readCart().filter((line) => line.productId !== productId));
}

export function clearCart(): void {
  writeCart([]);
}

/**
 * Nombre d'articles du panier (somme des quantités).
 */
export function cartCount(): number {
  return readCart().reduce((count, line) => count + line.quantity, 0);
}

/**
 * Abonnement aux changements du panier (cet onglet et les autres onglets).
 * Prévu pour useSyncExternalStore.
 */
export function subscribeToCart(listener: () => void): () => void {
  if (!hasWindow()) {
    return () => {};
  }

  const onStorage = (event: StorageEvent) => {
    if (event.key === null || event.key === CART_STORAGE_KEY) {
      listener();
    }
  };

  window.addEventListener(CART_CHANGED_EVENT, listener);
  window.addEventListener('storage', onStorage);

  return () => {
    window.removeEventListener(CART_CHANGED_EVENT, listener);
    window.removeEventListener('storage', onStorage);
  };
}

/**
 * Valeur brute du panier, stable entre deux lectures tant que le panier ne change pas.
 */
export function getCartSnapshot(): string {
  if (!hasWindow()) {
    return '[]';
  }

  return window.localStorage.getItem(CART_STORAGE_KEY) ?? '[]';
}
