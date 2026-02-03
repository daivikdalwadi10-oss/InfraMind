import React from 'react';

export default function Navbar({ user: _user, role }: { user?: { name?: string }; role?: string }) {
  return (
    <nav className="w-full border-b border-slate-200/60 bg-white/80 backdrop-blur">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
            IM
          </div>
          <div>
            <div className="text-sm font-semibold text-slate-900">InfraMind</div>
            <div className="text-xs text-slate-500">Operational intelligence</div>
          </div>
        </div>
        <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
          {role ?? 'Unauthenticated'}
        </div>
      </div>
    </nav>
  );
}
