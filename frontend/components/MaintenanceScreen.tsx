"use client";

import { AlertTriangle } from 'lucide-react';

export function MaintenanceScreen({ message }: { message?: string | null }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-6">
      <div className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white/80 p-8 text-center shadow-glass">
        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
          <AlertTriangle className="h-6 w-6" />
        </div>
        <h1 className="mt-4 text-2xl font-semibold text-ink">Maintenance in progress</h1>
        <p className="mt-2 text-sm text-muted">
          {message || 'InfraMind is temporarily unavailable while platform updates are applied.'}
        </p>
        <p className="mt-4 text-xs uppercase tracking-[0.3em] text-muted">Please check back soon</p>
      </div>
    </div>
  );
}
