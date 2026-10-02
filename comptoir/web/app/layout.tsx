import type { Metadata } from 'next';
import type { ReactNode } from 'react';
import { SiteHeader } from '@/components/SiteHeader';
import './globals.css';

export const metadata: Metadata = {
  title: 'Comptoir',
  description: 'Fournitures professionnelles : outillage, quincaillerie, livres techniques.',
};

export default function RootLayout({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <html lang="fr">
      <body>
        <SiteHeader />
        <div className="container">{children}</div>
      </body>
    </html>
  );
}
