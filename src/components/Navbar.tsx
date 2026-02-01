import React from 'react';

export default function Navbar({ user: _user, role }: { user?: { name?: string }; role?: string }) {
  return (
    <nav className="w-full p-4 bg-slate-50 border-b">
      <div className="max-w-6xl mx-auto flex items-center justify-between">
        <div className="text-lg font-semibold">InfraMind</div>
        <div className="text-sm text-slate-600">{role ?? 'Unauthenticated'}</div>
      </div>
    </nav>
  );
}
