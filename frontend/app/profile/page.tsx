'use client';

import { useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useSession } from '@/hooks/useSession';

export default function ProfilePage() {
  const router = useRouter();
  const { user, signOut, status } = useSession();

  const handleLogout = () => {
    signOut();
    router.push('/login');
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Profile</h1>
          <p className="text-sm text-muted">Account and role information.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view your profile.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Profile details</CardTitle>
            <CardDescription>Session data from backend.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            <div>
              <p className="text-xs uppercase text-muted">Name</p>
              <p className="text-sm text-ink">{user?.displayName ?? 'Unknown'}</p>
            </div>
            <div>
              <p className="text-xs uppercase text-muted">Email</p>
              <p className="text-sm text-ink">{user?.email ?? 'Unknown'}</p>
            </div>
            <div>
              <p className="text-xs uppercase text-muted">Role</p>
              <p className="text-sm text-ink">{user?.role ?? 'Unknown'}</p>
            </div>
            <Button variant="secondary" onClick={handleLogout}>
              Logout
            </Button>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
