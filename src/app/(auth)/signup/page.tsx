"use client";

import React from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import GlassCard from '@/src/components/ui/GlassCard';
import { signupAction } from '@/src/app/actions';
import type { Role } from '@/src/lib/types';

export default function SignupPage() {
  const router = useRouter();
  const [pending, startTransition] = React.useTransition();
  const [error, setError] = React.useState('');

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');
    const formData = new FormData(event.currentTarget);
    const email = String(formData.get('email') ?? '');
    const password = String(formData.get('password') ?? '');
    const name = String(formData.get('displayName') ?? '');
    const role = String(formData.get('role') ?? 'EMPLOYEE') as Role;

    startTransition(async () => {
      try {
        // Call PHP backend signup
        const user = await signupAction({ email, password, name, role });
        
        if (!user) {
          setError('Signup failed. Please try again.');
          return;
        }
        
        // Redirect based on role
        const target = role === 'MANAGER' ? '/manager' : role === 'OWNER' ? '/owner' : '/employee';
        router.push(target);
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        
        // Provide more helpful error messages
        if (message.includes('already exists') || message.includes('duplicate')) {
          setError('This email is already registered. Try logging in instead.');
        } else if (message.includes('password')) {
          setError('Password should be at least 6 characters.');
        } else if (message.includes('email')) {
          setError('Please enter a valid email address.');
        } else {
          setError(message || 'Signup failed. Please try again.');
        }
      }
    });
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <main className="mx-auto flex max-w-md flex-col gap-6 px-6 py-16">
        <header className="space-y-2 text-center">
          <h1 className="text-2xl font-semibold text-white">Create your InfraMind account</h1>
          <p className="text-sm text-slate-400">Choose your role to get started.</p>
        </header>

        <GlassCard>
          <form onSubmit={handleSubmit} className="space-y-4">
            <input
              name="displayName"
              type="text"
              placeholder="Full name"
              required
              className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none"
            />
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
            <select
              name="role"
              defaultValue="EMPLOYEE"
              className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-blue-400 focus:outline-none"
            >
              <option value="EMPLOYEE">Employee</option>
              <option value="MANAGER">Manager</option>
              <option value="OWNER">Owner</option>
            </select>
            {error && <p className="text-xs text-rose-400">{error}</p>}
            <button
              type="submit"
              disabled={pending}
              className="w-full rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {pending ? 'Creating account…' : 'Create account'}
            </button>
          </form>
        </GlassCard>

        <p className="text-center text-xs text-slate-400">
          Already have an account?{' '}
          <Link href="/login" className="text-blue-300 hover:text-blue-200">
            Sign in
          </Link>
        </p>
      </main>
    </div>
  );
}
