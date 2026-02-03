"use client";

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { ClipboardList, FileText, LayoutGrid, ShieldCheck, User } from 'lucide-react';

export type SidebarNavProps = {
  role?: 'employee' | 'manager' | 'owner' | 'authenticated';
};

type NavItem = {
  id: string;
  label: string;
  href: string;
  icon: React.ReactNode;
};

const roleNavMap: Record<string, NavItem[]> = {
  employee: [
    { id: 'employee-dashboard', label: 'Dashboard', href: '/employee', icon: <LayoutGrid className="h-4 w-4" /> },
    { id: 'employee-analyses', label: 'My Analyses', href: '/employee', icon: <ClipboardList className="h-4 w-4" /> },
  ],
  manager: [
    { id: 'manager-dashboard', label: 'Dashboard', href: '/manager', icon: <LayoutGrid className="h-4 w-4" /> },
    { id: 'manager-review', label: 'Review Queue', href: '/manager?taskStatus=IN_PROGRESS', icon: <ShieldCheck className="h-4 w-4" /> },
  ],
  owner: [
    { id: 'owner-dashboard', label: 'Dashboard', href: '/owner', icon: <LayoutGrid className="h-4 w-4" /> },
    { id: 'owner-reports', label: 'Reports', href: '/owner', icon: <FileText className="h-4 w-4" /> },
  ],
  authenticated: [
    { id: 'auth-dashboard', label: 'Dashboard', href: '/employee', icon: <LayoutGrid className="h-4 w-4" /> },
  ],
};

function resolveRole(pathname: string): SidebarNavProps['role'] {
  if (pathname.includes('/manager')) return 'manager';
  if (pathname.includes('/owner')) return 'owner';
  if (pathname.includes('/employee')) return 'employee';
  return 'authenticated';
}

export default function SidebarNav({ role }: SidebarNavProps) {
  const pathname = usePathname();
  const resolvedRole = role ?? resolveRole(pathname);
  const navItems = roleNavMap[resolvedRole as keyof typeof roleNavMap] ?? roleNavMap.authenticated;

  return (
    <aside className="fixed left-0 top-0 flex h-full w-64 flex-col gap-8 border-r border-white/10 bg-slate-950/70 p-6 backdrop-blur-xl">
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-semibold text-white">IM</div>
        <div>
          <div className="text-sm font-semibold text-white">InfraMind</div>
          <div className="text-xs text-slate-400">Operational intelligence</div>
        </div>
      </div>

      <nav className="flex flex-1 flex-col gap-2">
        {(navItems as NavItem[]).map((item: NavItem) => {
          const active = pathname === item.href || pathname.startsWith(item.href);
          return (
            <Link
              key={item.id}
              href={item.href}
              className={`flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition ${
                active ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'
              }`}
            >
              {item.icon}
              {item.label}
            </Link>
          );
        })}
      </nav>

      <div className="rounded-2xl border border-white/10 bg-white/5 p-3 text-xs text-slate-300">
        <div className="flex items-center gap-2">
          <User className="h-4 w-4" />
          <span className="font-semibold capitalize">{resolvedRole}</span>
        </div>
        <p className="mt-2 text-[11px] text-slate-400">Role-aware access enabled.</p>
      </div>
    </aside>
  );
}
