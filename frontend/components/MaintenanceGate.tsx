"use client";

import { useEffect, useMemo, useState } from 'react';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import { AnnouncementBar } from '@/components/AnnouncementBar';
import { MaintenanceScreen } from '@/components/MaintenanceScreen';
import type { MaintenanceState } from '@/lib/types';

const adminRoles = new Set(['DEVELOPER', 'SYSTEM_ADMIN']);

export function MaintenanceGate({ children }: { children: React.ReactNode }) {
  const { user, accessToken, status } = useSession();
  const [state, setState] = useState<MaintenanceState | null>(null);
  const isAdmin = useMemo(() => (user?.role ? adminRoles.has(user.role) : false), [user?.role]);

  useEffect(() => {
    let mounted = true;

    async function load() {
      const response = await apiRequest<MaintenanceState>('GET', '/maintenance/status');
      if (!mounted) return;
      if (response.success && response.data) {
        setState(response.data);
      }
    }

    void load();
    return () => {
      mounted = false;
    };
  }, []);

  const maintenanceEnabled = Boolean(state?.maintenanceEnabled || state?.softShutdownEnabled);

  if (maintenanceEnabled && !isAdmin) {
    return <MaintenanceScreen message={state?.maintenanceMessage} />;
  }

  return (
    <>
      <AnnouncementBar token={accessToken} enabled={status === 'authenticated'} />
      {children}
    </>
  );
}
