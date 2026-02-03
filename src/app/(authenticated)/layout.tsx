import React from 'react';
import { redirect } from 'next/navigation';
import AppShell from '@/src/components/AppShell';
import { getCurrentUserProfile } from '@/src/app/actions';

export default async function AuthenticatedLayout({ children }: { children: React.ReactNode }) {
  let profile = null;
  try {
    profile = await getCurrentUserProfile();
  } catch {
    redirect('/login');
  }
  return <AppShell user={{ displayName: profile?.name, role: profile?.role }}>{children}</AppShell>;
}
