import Image from 'next/image';
import { notFound } from 'next/navigation';
import { AddToCartForm } from '@/components/AddToCartForm';
import { ProductDescription } from '@/components/ProductDescription';
import { getProduct } from '@/lib/api';
import { formatPrice } from '@/lib/money';

export const dynamic = 'force-dynamic';

type ProductPageProps = {
  params: Promise<{ slug: string }>;
};

export default async function ProductPage({ params }: ProductPageProps) {
  const { slug } = await params;
  const product = await getProduct(slug);

  if (!product) {
    notFound();
  }

  return (
    <main className="product">
      <Image
        src={product.image_url ?? '/images/produits/placeholder.svg'}
        alt={product.name}
        width={320}
        height={240}
        className="product__image"
      />
      <div className="product__details">
        <h1>{product.name}</h1>
        <p className="product__price">{formatPrice(product.price_cents)}</p>
        <dl className="product__facts">
          <dt>SKU</dt>
          <dd>{product.sku}</dd>
          <dt>Catégorie</dt>
          <dd>{product.category.name}</dd>
          <dt>Stock</dt>
          <dd>{product.stock}</dd>
        </dl>
        <ProductDescription description={product.description} />
        <AddToCartForm
          item={{
            productId: product.id,
            slug: product.slug,
            sku: product.sku,
            name: product.name,
            unitPriceCents: product.price_cents,
          }}
        />
      </div>
    </main>
  );
}
