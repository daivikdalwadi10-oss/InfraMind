import React from 'react';
import GlassCard from './GlassCard';

type AnalysisSectionProps = {
  title: string;
  subtitle?: string;
  disabled?: boolean;
  children: React.ReactNode;
  footer?: React.ReactNode;
};

export default function AnalysisSection({ title, subtitle, disabled, children, footer }: AnalysisSectionProps) {
  return (
    <GlassCard className={disabled ? 'opacity-60' : ''}>
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-base font-semibold text-white">{title}</h3>
          {subtitle && <p className="mt-1 text-xs text-slate-400">{subtitle}</p>}
        </div>
        {disabled && (
          <span className="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-[10px] uppercase tracking-wide text-slate-400">
            Locked
          </span>
        )}
      </div>
      <div className="mt-4 space-y-3">{children}</div>
      {footer && <div className="mt-4 border-t border-white/10 pt-3">{footer}</div>}
    </GlassCard>
  );
}
