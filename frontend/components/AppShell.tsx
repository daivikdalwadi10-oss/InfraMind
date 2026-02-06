'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { SidebarNav } from '@/components/SidebarNav';
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
    <div className="min-h-screen bg-surface">
      <div className="flex min-h-screen">
        <aside className="w-64 border-r border-slate-200 bg-white/70 px-4 py-6">
          <Link href="/dashboard" className="text-xl font-semibold text-ink">
            InfraMind
          </Link>
          <p className="mt-1 text-xs uppercase tracking-wide text-muted">Enterprise Workspace</p>
          <div className="mt-8">
            <SidebarNav />
          </div>
        </aside>
        <main className="flex-1">
          <header className="flex items-center justify-between border-b border-slate-200 bg-white/60 px-4 py-2">
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Signed in</p>
              <p className="text-sm font-medium text-ink">{user?.displayName || 'Loading...'}</p>
              <p className="text-xs text-muted">Role: {role ?? 'Unknown'}</p>
            </div>
            <Button variant="ghost" onClick={handleLogout} className="gap-2">
              <LogOut className="h-4 w-4" />
              Logout
            </Button>
          </header>
          <div className="px-8 py-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
