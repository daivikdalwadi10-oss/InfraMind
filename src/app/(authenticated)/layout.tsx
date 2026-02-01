import React from 'react';
import Navbar from '@/src/components/Navbar';

export default function AuthenticatedLayout({ children }: { children: React.ReactNode }) {
  // In a full app, we'd resolve the user server-side and pass props. For now, scaffolded.
  return (
    <div>
      <Navbar role={"authenticated"} />
      <main className="max-w-6xl mx-auto p-6">{children}</main>
    </div>
  );
}
