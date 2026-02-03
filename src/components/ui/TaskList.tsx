import React from 'react';
import Link from 'next/link';
import StatusBadge from './StatusBadge';
import GlassCard from './GlassCard';

export type TaskListItem = {
  id: string;
  title: string;
  description?: string;
  status: string;
  href?: string;
  meta?: string;
  ctaLabel?: string;
};

type TaskListProps = {
  title: string;
  items: TaskListItem[];
  emptyMessage: string;
};

export default function TaskList({ title, items, emptyMessage }: TaskListProps) {
  return (
    <GlassCard>
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-white">{title}</h2>
        <span className="text-xs text-slate-400">{items.length} items</span>
      </div>
      <div className="mt-4 divide-y divide-white/10">
        {items.length === 0 && <p className="py-3 text-sm text-slate-400">{emptyMessage}</p>}
        {items.map((item) => (
          <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
            <div>
              <div className="text-sm font-semibold text-white">{item.title}</div>
              {item.description && <p className="mt-1 text-xs text-slate-400">{item.description}</p>}
              {item.meta && <p className="mt-2 text-[11px] uppercase tracking-wide text-slate-500">{item.meta}</p>}
            </div>
            <div className="flex items-center gap-3">
              <StatusBadge status={item.status} />
              {item.href && item.ctaLabel && (
                <Link
                  href={item.href}
                  className="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20"
                >
                  {item.ctaLabel}
                </Link>
              )}
            </div>
          </div>
        ))}
      </div>
    </GlassCard>
  );
}
