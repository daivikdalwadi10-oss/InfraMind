'use client';

import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Incident, IncidentSeverity, UserProfile } from '@/lib/types';

const severityOptions: IncidentSeverity[] = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];

export default function IncidentsPage() {
  const { user, accessToken, status } = useSession();
  const [incidents, setIncidents] = useState<Incident[]>([]);
  const [users, setUsers] = useState<UserProfile[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [severity, setSeverity] = useState<IncidentSeverity>('MEDIUM');
  const [assignedTo, setAssignedTo] = useState('');
  const [occurredAt, setOccurredAt] = useState('');

  const role = user?.role ?? null;
  const canCreate = role === 'EMPLOYEE' || role === 'MANAGER';
  const canAssign = role === 'MANAGER' || role === 'OWNER';

  const loadIncidents = async () => {
    if (status !== 'authenticated' || !accessToken) return;
    setLoading(true);
    setError(null);
    const response = await apiRequest<Incident[]>('GET', '/incidents', undefined, accessToken);
    if (!response.success || !response.data) {
      setError(response.error || 'Unable to load incidents.');
      setIncidents([]);
      setLoading(false);
      return;
    }
    setIncidents(response.data);
    setLoading(false);
  };

  const loadUsers = async () => {
    if (!canAssign || status !== 'authenticated' || !accessToken) return;
    const response = await apiRequest<UserProfile[]>('GET', '/users', undefined, accessToken);
    if (!response.success || !response.data) {
      setUsers([]);
      return;
    }
    setUsers(response.data);
  };

  useEffect(() => {
    void loadIncidents();
    void loadUsers();
  }, [status, accessToken, canAssign]);

  const handleCreateIncident = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<Incident>(
      'POST',
      '/incidents',
      {
        title,
        description: description || null,
        severity,
        assignedTo: assignedTo || null,
        occurredAt: occurredAt || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create incident.');
      return;
    }

    setTitle('');
    setDescription('');
    setSeverity('MEDIUM');
    setAssignedTo('');
    setOccurredAt('');
    void loadIncidents();
  };

  const userById = useMemo(() => {
    return users.reduce<Record<string, UserProfile>>((acc, userItem) => {
      acc[userItem.id] = userItem;
      return acc;
    }, {});
  }, [users]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Incidents</h1>
          <p className="text-sm text-muted">Operational incidents across the workspace.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to manage incidents.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {canCreate ? (
          <Card>
            <CardHeader>
              <CardTitle>Report incident</CardTitle>
              <CardDescription>Log a new incident for tracking and response.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateIncident}>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Title</label>
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Description</label>
                  <textarea
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    rows={3}
                    value={description}
                    onChange={(event) => setDescription(event.target.value)}
                  />
                </div>
                <div className="grid gap-3 md:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Severity</label>
                    <select
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={severity}
                      onChange={(event) => setSeverity(event.target.value as IncidentSeverity)}
                    >
                      {severityOptions.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Occurred at</label>
                    <input
                      type="datetime-local"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={occurredAt}
                      onChange={(event) => setOccurredAt(event.target.value)}
                    />
                  </div>
                  {canAssign ? (
                    <div className="space-y-1">
                      <label className="text-xs uppercase text-muted">Assign to</label>
                      <select
                        className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                        value={assignedTo}
                        onChange={(event) => setAssignedTo(event.target.value)}
                      >
                        {users.length === 0 ? (
                          <option value="" disabled>
                            No assignees available
                          </option>
                        ) : (
                          <option value="">Unassigned</option>
                        )}
                        {users
                          .filter((item) => item.role !== 'OWNER')
                          .map((member) => (
                            <option key={member.id} value={member.id}>
                              {member.displayName} ({member.email})
                            </option>
                          ))}
                      </select>
                    </div>
                  ) : null}
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Reporting...' : 'Report incident'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Incident list</CardTitle>
            <CardDescription>Latest incidents from backend.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {loading ? <p className="text-sm text-muted">Loading incidents...</p> : null}
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            {!loading && !error && incidents.length === 0 ? (
              <p className="text-sm text-muted">No incidents found.</p>
            ) : null}
            <div className="grid gap-3">
              {incidents.map((incident) => (
                <Card key={incident.id}>
                  <CardHeader>
                    <CardTitle>{incident.title}</CardTitle>
                    <CardDescription>{incident.description || 'No description'}</CardDescription>
                  </CardHeader>
                  <CardContent className="flex flex-wrap items-center gap-2">
                    <Badge>{incident.status}</Badge>
                    <Badge variant={incident.severity === 'CRITICAL' ? 'danger' : 'warning'}>
                      {incident.severity}
                    </Badge>
                    {incident.assignedTo ? (
                      <span className="text-xs text-muted">
                        Assigned: {userById[incident.assignedTo]?.displayName ?? incident.assignedTo}
                      </span>
                    ) : null}
                    {incident.occurredAt ? (
                      <span className="text-xs text-muted">Occurred: {incident.occurredAt}</span>
                    ) : null}
                    {incident.resolvedAt ? (
                      <span className="text-xs text-muted">Resolved: {incident.resolvedAt}</span>
                    ) : null}
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
