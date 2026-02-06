'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { loginWithEmailPassword, useSession } from '@/hooks/useSession';

export default function LoginPage() {
  const router = useRouter();
  const { status, refresh } = useSession();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (status === 'authenticated') {
      router.replace('/dashboard');
    }
  }, [status, router]);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const response = await loginWithEmailPassword(email, password);

    if (!response.success) {
      setError(response.error || 'Login failed. Please try again.');
      setLoading(false);
      return;
    }

    await refresh();
    setLoading(false);
    router.replace('/dashboard');
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-6">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle>InfraMind</CardTitle>
          <CardDescription>Sign in to your enterprise workspace.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <label className="text-xs uppercase text-muted">Email</label>
              <input
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="user@example.com"
                required
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs uppercase text-muted">Password</label>
              <input
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="••••••••"
                required
              />
            </div>
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? 'Signing in…' : 'Sign in'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
