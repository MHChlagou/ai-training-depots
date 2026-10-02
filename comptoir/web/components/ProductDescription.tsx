type ProductDescriptionProps = {
  description: string | null | undefined;
};

/**
 * Affiche la description d'un produit comme du texte.
 * Les paragraphes sont séparés par une ligne vide dans la source.
 */
export function ProductDescription({ description }: ProductDescriptionProps) {
  if (!description) {
    return null;
  }

  const paragraphs = description
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean);

  if (paragraphs.length === 0) {
    return null;
  }

  return (
    <div className="prose" data-testid="product-description">
      {paragraphs.map((paragraph, index) => (
        <p key={index}>{paragraph}</p>
      ))}
    </div>
  );
}
