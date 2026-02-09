'use client';

import { SessionProvider } from '@/hooks/useSession';
import { MaintenanceGate } from '@/components/MaintenanceGate';
import { ThemeProvider } from '@/components/ThemeProvider';

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <SessionProvider>
      <ThemeProvider>
        <MaintenanceGate>{children}</MaintenanceGate>
      </ThemeProvider>
    </SessionProvider>
  );
}
