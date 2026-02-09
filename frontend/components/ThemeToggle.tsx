'use client';

import * as React from 'react';
import { Laptop, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useTheme } from '@/components/ThemeProvider';

type ThemeOption = {
  value: 'light' | 'dark' | 'system';
  label: string;
  icon: React.ComponentType<{ className?: string }>;
};

const options: ThemeOption[] = [
  { value: 'light', label: 'Light', icon: Sun },
  { value: 'dark', label: 'Dark', icon: Moon },
  { value: 'system', label: 'System', icon: Laptop },
];

export function ThemeToggle({ className }: { className?: string }) {
  const { theme, setTheme } = useTheme();

  return (
    <div
      className={cn(
        'flex items-center gap-1 rounded-full border border-white/40 bg-white/50 p-1 text-xs backdrop-blur dark:border-slate-700/60 dark:bg-slate-900/50',
        className,
      )}
      role="group"
      aria-label="Theme"
    >
      {options.map((option) => {
        const Icon = option.icon;
        const isActive = theme === option.value;
        return (
          <Button
            key={option.value}
            type="button"
            size="sm"
            variant="ghost"
            onClick={() => setTheme(option.value)}
            aria-pressed={isActive}
            aria-label={option.label}
            className={cn(
              'h-7 gap-1 rounded-full px-2 text-xs font-medium',
              isActive
                ? 'bg-ink text-white hover:bg-ink/90 dark:bg-white dark:text-slate-900 dark:hover:bg-white/90'
                : 'text-ink hover:bg-white/70 dark:text-slate-100 dark:hover:bg-slate-800',
            )}
          >
            <Icon className="h-3.5 w-3.5" />
            <span className="hidden sm:inline">{option.label}</span>
          </Button>
        );
      })}
    </div>
  );
}
