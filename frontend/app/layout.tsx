import '../styles/globals.css';
import Script from 'next/script';
import { Providers } from './providers';

export const metadata = {
  title: 'InfraMind',
  description: 'Enterprise Dashboard SPA',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <Script id="theme-init" strategy="beforeInteractive">
          {`(function(){try{var t=localStorage.getItem('inframind-theme')||'system';var m=window.matchMedia('(prefers-color-scheme: dark)').matches;var d=t==='dark'||(t==='system'&&m);var r=document.documentElement;if(d){r.classList.add('dark');}else{r.classList.remove('dark');}r.style.colorScheme=d?'dark':'light';}catch(e){}})();`}
        </Script>
      </head>
      <body className="min-h-screen bg-surface text-ink" suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
