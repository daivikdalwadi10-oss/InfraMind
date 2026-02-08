'use client';

import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { InfrastructureState, InfrastructureStatus } from '@/lib/types';

const statusOptions: InfrastructureStatus[] = ['HEALTHY', 'DEGRADED', 'OUTAGE', 'MAINTENANCE'];

export default function InfrastructurePage() {
  const { user, accessToken, status } = useSession();
  const [states, setStates] = useState<InfrastructureState[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [component, setComponent] = useState('');
  const [summary, setSummary] = useState('');
  const [stateStatus, setStateStatus] = useState<InfrastructureStatus>('HEALTHY');
  const [observedAt, setObservedAt] = useState('');

  const role = user?.role ?? null;
  const canCreate = role === 'MANAGER';

  const loadStates = async () => {
    if (status !== 'authenticated' || !accessToken) return;
    setLoading(true);
    setError(null);
    const response = await apiRequest<InfrastructureState[]>('GET', '/infrastructure', undefined, accessToken);
    if (!response.success || !response.data) {
      setError(response.error || 'Unable to load infrastructure states.');
      setStates([]);
      setLoading(false);
      return;
    }
    setStates(response.data);
    setLoading(false);
  };

  useEffect(() => {
    void loadStates();
  }, [status, accessToken]);

  const handleCreateState = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<InfrastructureState>(
      'POST',
      '/infrastructure',
      {
        component,
        status: stateStatus,
        summary: summary || null,
        observedAt: observedAt || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create infrastructure state.');
      return;
    }

    setComponent('');
    setSummary('');
    setStateStatus('HEALTHY');
    setObservedAt('');
    void loadStates();
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Infrastructure</h1>
          <p className="text-sm text-muted">Track infrastructure health signals.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view infrastructure states.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {canCreate ? (
          <Card>
            <CardHeader>
              <CardTitle>Record infrastructure state</CardTitle>
              <CardDescription>Log a component status update.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateState}>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Component</label>
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={component}
                    onChange={(event) => setComponent(event.target.value)}
                    required
                  />
                </div>
                <div className="grid gap-3 md:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Status</label>
                    <select
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={stateStatus}
                      onChange={(event) => setStateStatus(event.target.value as InfrastructureStatus)}
                    >
                      {statusOptions.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Observed at</label>
                    <input
                      type="datetime-local"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={observedAt}
                      onChange={(event) => setObservedAt(event.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Summary</label>
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={summary}
                      onChange={(event) => setSummary(event.target.value)}
                    />
                  </div>
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Saving...' : 'Record state'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>State list</CardTitle>
            <CardDescription>Latest recorded signals.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {loading ? <p className="text-sm text-muted">Loading states...</p> : null}
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            {!loading && !error && states.length === 0 ? (
              <p className="text-sm text-muted">No states recorded.</p>
            ) : null}
            <div className="grid gap-3">
              {states.map((item) => (
                <Card key={item.id}>
                  <CardHeader>
                    <CardTitle>{item.component}</CardTitle>
                    <CardDescription>{item.summary || 'No summary'}</CardDescription>
                  </CardHeader>
                  <CardContent className="flex flex-wrap items-center gap-2">
                    <Badge>{item.status}</Badge>
                    <span className="text-xs text-muted">Observed: {item.observedAt}</span>
                  </CardContent>
                </Card>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
