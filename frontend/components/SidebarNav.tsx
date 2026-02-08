'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  AlertTriangle,
  Calendar,
  ClipboardCheck,
  FileText,
  LayoutDashboard,
  ListChecks,
  Server,
  Shield,
  ShieldCheck,
  UserCircle,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { getRole } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';

const navItems = [
  { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['EMPLOYEE', 'MANAGER', 'OWNER'] },
  { label: 'Tasks', href: '/tasks', icon: ClipboardCheck, roles: ['EMPLOYEE', 'MANAGER'] },
  { label: 'Analyses', href: '/analysis', icon: ListChecks, roles: ['EMPLOYEE', 'MANAGER'] },
  { label: 'Reviews', href: '/reviews', icon: ShieldCheck, roles: ['MANAGER'] },
  { label: 'Incidents', href: '/incidents', icon: AlertTriangle, roles: ['EMPLOYEE', 'MANAGER', 'OWNER'] },
  { label: 'Infrastructure', href: '/infrastructure', icon: Server, roles: ['MANAGER', 'OWNER'] },
  { label: 'Risks', href: '/risks', icon: Shield, roles: ['MANAGER', 'OWNER'] },
  { label: 'Meetings', href: '/meetings', icon: Calendar, roles: ['MANAGER', 'OWNER'] },
  { label: 'Management', href: '/management', icon: ShieldCheck, roles: ['MANAGER', 'OWNER'] },
  { label: 'Reports', href: '/reports', icon: FileText, roles: ['MANAGER', 'OWNER'] },
  { label: 'Profile', href: '/profile', icon: UserCircle, roles: ['EMPLOYEE', 'MANAGER', 'OWNER'] },
  { label: 'Developer', href: '/developer', icon: Shield, roles: ['DEVELOPER', 'SYSTEM_ADMIN'] },
];

export function SidebarNav() {
  const pathname = usePathname();
  const { user } = useSession();
  const role = getRole(user);

  return (
    <nav className="space-y-1">
      {navItems
        .filter((item) => (role ? item.roles.includes(role) : false))
        .map((item) => {
          const Icon = item.icon;
          const isActive = pathname.startsWith(item.href);
          return (
            <Link
              key={item.label}
              href={item.href}
              className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                isActive ? 'bg-ink text-white' : 'text-ink hover:bg-slate-100',
              )}
            >
              <Icon className="h-4 w-4" />
              {item.label}
            </Link>
          );
        })}
    </nav>
  );
}
