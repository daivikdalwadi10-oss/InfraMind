'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { SidebarNav } from '@/components/SidebarNav';
import { ThemeToggle } from '@/components/ThemeToggle';
import { useSession } from '@/hooks/useSession';
import { getRole } from '@/lib/auth';

export function AppShell({ children }: { children: React.ReactNode }) {
  const { user, signOut, status, error, refresh } = useSession();
  const router = useRouter();
  const role = getRole(user);

  const handleLogout = () => {
    signOut();
    router.push('/login');
  };

  if (status === 'unauthenticated') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-surface">
        <div className="text-center">
          <p className="text-sm text-muted">Session expired. Please sign in again.</p>
          <Button variant="secondary" onClick={() => router.push('/login')} className="mt-4">
            Go to login
          </Button>
        </div>
      </div>
    );
  }

  if (status === 'error') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-surface">
        <div className="text-center space-y-3">
          <p className="text-sm text-muted">Unable to load your session.</p>
          {error ? <p className="text-xs text-rose-600">{error}</p> : null}
          <div className="flex items-center justify-center gap-2">
            <Button variant="secondary" onClick={() => void refresh()}>
              Retry
            </Button>
            <Button variant="ghost" onClick={() => router.push('/login')}>
              Go to login
            </Button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 text-ink dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
      <div className="flex min-h-screen">
        <aside className="glass-panel w-64 border-r px-4 py-6">
          <Link href="/dashboard" className="text-xl font-semibold text-ink">
            InfraMind
          </Link>
          <p className="mt-1 text-xs uppercase tracking-wide text-muted">Enterprise Workspace</p>
          <div className="mt-8">
            <SidebarNav />
          </div>
        </aside>
        <main className="flex-1">
          <header className="flex items-center justify-between border-b border-slate-200/60 bg-gradient-to-r from-white/70 via-white/50 to-white/30 px-4 py-3 backdrop-blur dark:border-slate-800/60 dark:from-slate-900/70 dark:via-slate-900/50 dark:to-slate-800/40">
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Signed in</p>
              <p className="text-sm font-medium text-ink">{user?.displayName || 'Loading...'}</p>
              <p className="text-xs text-muted">Role: {role ?? 'Unknown'}</p>
            </div>
            <div className="flex items-center gap-3">
              <ThemeToggle />
              <Button variant="ghost" onClick={handleLogout} className="gap-2">
                <LogOut className="h-4 w-4" />
                Logout
              </Button>
            </div>
          </header>
          <div className="px-6 py-6 md:px-8">{children}</div>
        </main>
      </div>
    </div>
  );
}
