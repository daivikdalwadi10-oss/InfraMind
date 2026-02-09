import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center rounded-full border border-transparent px-2.5 py-0.5 text-xs font-semibold transition',
  {
    variants: {
      variant: {
        default: 'bg-slate-100 text-ink dark:bg-slate-800 dark:text-slate-100',
        success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
        warning: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
        danger: 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
