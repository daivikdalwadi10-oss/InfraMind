import React from 'react';

type ReadinessMeterProps = {
  score: number;
};

export default function ReadinessMeter({ score }: ReadinessMeterProps) {
  const clamped = Math.max(0, Math.min(100, score));
  const status = clamped >= 75 ? 'Ready to submit' : 'Needs more signal';
  return (
    <div className="rounded-2xl border border-white/10 bg-white/5 p-4">
      <div className="flex items-center justify-between text-xs text-slate-400">
        <span>Readiness score</span>
        <span className="text-sm font-semibold text-white">{clamped}%</span>
      </div>
      <progress
        value={clamped}
        max={100}
        className="mt-3 h-2 w-full overflow-hidden rounded-full bg-white/10 accent-blue-400"
      />
      <p className="mt-2 text-xs text-slate-400">{status}</p>
    </div>
  );
}
