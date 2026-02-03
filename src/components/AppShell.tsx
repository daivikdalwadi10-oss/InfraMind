"use client";

import React from 'react';
import { usePathname, useRouter } from 'next/navigation';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { Bell, ChevronDown, User } from 'lucide-react';
import SidebarNav from './ui/SidebarNav';
import { ToastProvider } from './ui/Toast';
import type { Role } from '@/src/lib/types';
import { logoutAction } from '@/src/app/actions';

const pageTitles: Record<string, { title: string; subtitle?: string }> = {
  '/employee': { title: 'Employee Dashboard', subtitle: 'Track your analyses and AI support.' },
  '/manager': { title: 'Manager Dashboard', subtitle: 'Review submissions and publish reports.' },
  '/owner': { title: 'Owner Dashboard', subtitle: 'Monitor generated reports.' },
};

function resolvePageMeta(pathname: string) {
  if (pathname.includes('/analysis/')) {
    return { title: 'Analysis Workbench', subtitle: 'Focus mode for structured analysis.' };
  }
  if (pathname.includes('/manager')) return pageTitles['/manager'];
  if (pathname.includes('/owner')) return pageTitles['/owner'];
  return pageTitles['/employee'];
}

export default function AppShell({
  children,
  user,
}: {
  children: React.ReactNode;
  user?: { displayName?: string; role?: Role };
}) {
  const pathname = usePathname();
  const meta = resolvePageMeta(pathname);
  const router = useRouter();
  const [loggingOut, startTransition] = React.useTransition();

  return (
    <ToastProvider>
      <div className="min-h-screen bg-slate-950 text-slate-100">
        <SidebarNav role={user?.role ? user.role.toLowerCase() as 'employee' | 'manager' | 'owner' : undefined} />
        <div className="ml-64 flex min-h-screen flex-col">
          <header className="sticky top-0 z-20 flex items-center justify-between border-b border-white/10 bg-slate-950/70 px-8 py-4 backdrop-blur-xl">
            <div>
              <div className="text-xl font-semibold text-white">{meta.title}</div>
              {meta.subtitle && <p className="text-xs text-slate-400">{meta.subtitle}</p>}
            </div>
            <div className="flex items-center gap-3">
              <button className="rounded-full border border-white/10 bg-white/5 p-2 text-slate-300 hover:text-white">
                <Bell className="h-4 w-4" />
              </button>
              <DropdownMenu.Root>
                <DropdownMenu.Trigger className="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200">
                  <User className="h-4 w-4" />
                  {user?.displayName ?? 'User'}
                  <ChevronDown className="h-3 w-3" />
                </DropdownMenu.Trigger>
                <DropdownMenu.Portal>
                  <DropdownMenu.Content className="z-50 min-w-[180px] rounded-xl border border-white/10 bg-slate-950/90 p-2 text-xs text-slate-200 shadow-xl shadow-black/40 backdrop-blur">
                    <DropdownMenu.Item className="rounded-lg px-2 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Profile</DropdownMenu.Item>
                    <DropdownMenu.Item className="rounded-lg px-2 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Settings</DropdownMenu.Item>
                    <DropdownMenu.Separator className="my-1 h-px bg-white/10" />
                    <DropdownMenu.Item
                      disabled={loggingOut}
                      onSelect={() => {
                        startTransition(async () => {
                          await logoutAction();
                          router.push('/login');
                        });
                      }}
                      className="rounded-lg px-2 py-2 text-slate-300 hover:bg-white/10 hover:text-white"
                    >
                      Sign out
                    </DropdownMenu.Item>
                  </DropdownMenu.Content>
                </DropdownMenu.Portal>
              </DropdownMenu.Root>
            </div>
          </header>
          <main className="flex-1 px-8 py-8">{children}</main>
        </div>
      </div>
    </ToastProvider>
  );
}
