'use client';

import { SessionProvider } from '@/hooks/useSession';
import { MaintenanceGate } from '@/components/MaintenanceGate';

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <SessionProvider>
      <MaintenanceGate>{children}</MaintenanceGate>
    </SessionProvider>
  );
}
