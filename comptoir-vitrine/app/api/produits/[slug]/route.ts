import { getProduit } from "@/lib/produits";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ slug: string }> }
) {
  const { slug } = await params;
  const produit = getProduit(slug);
  if (!produit) {
    return Response.json({ erreur: "Produit introuvable" }, { status: 404 });
  }
  return Response.json(produit);
}
