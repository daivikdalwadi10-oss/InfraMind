import '../styles/globals.css';
import { Providers } from './providers';

export const metadata = {
  title: 'InfraMind',
  description: 'Enterprise Dashboard SPA',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-surface text-ink" suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
