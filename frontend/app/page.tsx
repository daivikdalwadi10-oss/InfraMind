'use client';

import Link from 'next/link';
import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useSession } from '@/hooks/useSession';

export default function HomePage() {
  const { status, error } = useSession();
  const router = useRouter();

  useEffect(() => {
    if (status === 'authenticated') {
      router.replace('/dashboard');
      return;
    }
    if (status === 'unauthenticated') {
      router.replace('/login');
    }
  }, [status, router]);

  return (
    <div className="flex min-h-screen items-center justify-center text-sm text-muted">
      {status === 'error' ? (
        <div className="text-center space-y-2">
          <p>Unable to load session.</p>
          {error ? <p className="text-xs text-rose-600">{error}</p> : null}
          <Link className="text-ink underline" href="/login">
            Go to login
          </Link>
        </div>
      ) : (
        <p>Loading workspace...</p>
      )}
    </div>
  );
}
