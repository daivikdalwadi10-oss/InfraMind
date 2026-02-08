"use client";

import { useEffect, useState } from 'react';
import { AlertTriangle, Info, OctagonAlert } from 'lucide-react';
import { apiRequest } from '@/lib/api';
import type { Announcement } from '@/lib/types';

const severityIcon = {
  INFO: Info,
  WARNING: AlertTriangle,
  CRITICAL: OctagonAlert,
};

function getDismissed(): string[] {
  if (typeof window === 'undefined') return [];
  const raw = window.localStorage.getItem('inframind.dismissedAnnouncements');
  return raw ? raw.split(',').filter(Boolean) : [];
}

function setDismissed(ids: string[]) {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem('inframind.dismissedAnnouncements', ids.join(','));
}

export function AnnouncementBar({ token, enabled }: { token?: string | null; enabled: boolean }) {
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [dismissed, setDismissedState] = useState<string[]>(getDismissed());

  useEffect(() => {
    if (!enabled) return;
    let mounted = true;

    async function load() {
      const response = await apiRequest<Announcement[]>('GET', '/announcements/active', undefined, token);
      if (!mounted) return;
      if (response.success && response.data) {
        setAnnouncements(response.data);
      }
    }

    void load();
    return () => {
      mounted = false;
    };
  }, [enabled, token]);

  if (!enabled || announcements.length === 0) {
    return null;
  }

  const visible = announcements.filter((item) => !dismissed.includes(item.id));
  if (visible.length === 0) {
    return null;
  }

  return (
    <div className="space-y-3 px-6 pt-4">
      {visible.map((item) => {
        const Icon = severityIcon[item.severity] || Info;
        const dismissible = Boolean(item.dismissible ?? true);
        return (
          <div
            key={item.id}
            className="flex items-start justify-between gap-4 rounded-xl border border-amber-200/70 bg-amber-50/70 px-4 py-3 text-sm text-amber-900 shadow-sm"
          >
            <div className="flex items-start gap-3">
              <div className="mt-0.5 rounded-lg bg-white/70 p-1">
                <Icon className="h-4 w-4" />
              </div>
              <div>
                <p className="text-sm font-semibold text-amber-900">{item.title}</p>
                <p className="text-xs text-amber-900/80">{item.message}</p>
              </div>
            </div>
            {dismissible ? (
              <button
                className="text-xs font-medium text-amber-900/70 transition hover:text-amber-900"
                onClick={() => {
                  const updated = Array.from(new Set([...dismissed, item.id]));
                  setDismissed(updated);
                  setDismissedState(updated);
                  setAnnouncements((prev) => prev.filter((entry) => entry.id !== item.id));
                }}
              >
                Dismiss
              </button>
            ) : null}
          </div>
        );
      })}
    </div>
  );
}
