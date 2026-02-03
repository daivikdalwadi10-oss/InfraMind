"use client";

import React from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import GlassCard from '@/src/components/ui/GlassCard';
import { createSessionAction } from '@/src/app/actions';

export default function LoginPage() {
  const router = useRouter();
  const [pending, startTransition] = React.useTransition();
  const [error, setError] = React.useState('');

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');
    const formData = new FormData(event.currentTarget);
    const email = String(formData.get('email') ?? '');
    const password = String(formData.get('password') ?? '');

    startTransition(async () => {
      try {
        // Call PHP backend login
        const user = await createSessionAction({ email, password });
        
        if (!user) {
          setError('Login failed. Please check your credentials.');
          return;
        }
        
        // Redirect based on role
        const target = user.role === 'MANAGER' ? '/manager' : user.role === 'OWNER' ? '/owner' : '/employee';
        router.push(target);
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        setError(message || 'Login failed. Please try again.');
      }
    });
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <main className="mx-auto flex max-w-md flex-col gap-6 px-6 py-16">
        <header className="space-y-2 text-center">
          <h1 className="text-2xl font-semibold text-white">Sign in to InfraMind</h1>
          <p className="text-sm text-slate-400">Access your role-based workspace.</p>
        </header>

        <GlassCard>
          <form onSubmit={handleSubmit} className="space-y-4">
            <input
              name="email"
              type="email"
              placeholder="Email"
              required
              className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none"
            />
            <input
              name="password"
              type="password"
              placeholder="Password"
              required
              className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none"
            />
            {error && <p className="text-xs text-rose-400">{error}</p>}
            <button
              type="submit"
              disabled={pending}
              className="w-full rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {pending ? 'Signing in…' : 'Sign in'}
            </button>
          </form>
        </GlassCard>

        <p className="text-center text-xs text-slate-400">
          New here?{' '}
          <Link href="/signup" className="text-blue-300 hover:text-blue-200">
            Create an account
          </Link>
        </p>
      </main>
    </div>
  );
}
