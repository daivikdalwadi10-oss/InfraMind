import React from 'react';

type StatusBadgeProps = {
  status: string;
  className?: string;
};

const statusStyles: Record<string, string> = {
  DRAFT: 'text-slate-200 bg-slate-500/20 border-slate-400/40',
  NEEDS_CHANGES: 'text-amber-200 bg-amber-500/20 border-amber-400/40',
  APPROVED: 'text-emerald-200 bg-emerald-500/20 border-emerald-400/40',
  SUBMITTED: 'text-blue-200 bg-blue-500/20 border-blue-400/40',
  OPEN: 'text-slate-200 bg-slate-500/20 border-slate-400/40',
  IN_PROGRESS: 'text-blue-200 bg-blue-500/20 border-blue-400/40',
  COMPLETED: 'text-emerald-200 bg-emerald-500/20 border-emerald-400/40',
};

export default function StatusBadge({ status, className }: StatusBadgeProps) {
  const key = status.toUpperCase();
  const style = statusStyles[key] ?? 'text-slate-200 bg-slate-500/20 border-slate-400/40';
  return (
    <span
      className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ${style} ${
        className ?? ''
      }`}
    >
      {status}
    </span>
  );
}
