import React from 'react';
import { Star } from 'lucide-react';

type AIHintBoxProps = {
  title?: string;
  message: string;
};

export default function AIHintBox({ title = 'AI Suggestion', message }: AIHintBoxProps) {
  return (
    <div className="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4 text-sm text-slate-200">
      <div className="flex items-center gap-2 text-xs uppercase tracking-wide text-blue-200">
        <Star className="h-4 w-4" />
        {title}
      </div>
      <p className="mt-2 text-sm text-slate-200">{message}</p>
    </div>
  );
}
