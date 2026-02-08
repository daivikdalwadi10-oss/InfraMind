'use client';

import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { ArchitectureRisk, RiskSeverity, RiskStatus } from '@/lib/types';

const severityOptions: RiskSeverity[] = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
const statusOptions: RiskStatus[] = ['OPEN', 'MITIGATING', 'RESOLVED'];

export default function RisksPage() {
  const { user, accessToken, status } = useSession();
  const [risks, setRisks] = useState<ArchitectureRisk[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [severity, setSeverity] = useState<RiskSeverity>('MEDIUM');
  const [riskStatus, setRiskStatus] = useState<RiskStatus>('OPEN');
  const [analysisId, setAnalysisId] = useState('');

  const role = user?.role ?? null;
  const canCreate = role === 'MANAGER';

  const loadRisks = async () => {
    if (status !== 'authenticated' || !accessToken) return;
    setLoading(true);
    setError(null);
    const response = await apiRequest<ArchitectureRisk[]>('GET', '/risks', undefined, accessToken);
    if (!response.success || !response.data) {
      setError(response.error || 'Unable to load risks.');
      setRisks([]);
      setLoading(false);
      return;
    }
    setRisks(response.data);
    setLoading(false);
  };

  useEffect(() => {
    void loadRisks();
  }, [status, accessToken]);

  const handleCreateRisk = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<ArchitectureRisk>(
      'POST',
      '/risks',
      {
        title,
        description: description || null,
        severity,
        status: riskStatus,
        analysisId: analysisId || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create risk.');
      return;
    }

    setTitle('');
    setDescription('');
    setSeverity('MEDIUM');
    setRiskStatus('OPEN');
    setAnalysisId('');
    void loadRisks();
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Architecture Risks</h1>
          <p className="text-sm text-muted">Track strategic risks and mitigations.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view risks.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {canCreate ? (
          <Card>
            <CardHeader>
              <CardTitle>Log architecture risk</CardTitle>
              <CardDescription>Capture new risks for mitigation planning.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateRisk}>
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
                      onChange={(event) => setSeverity(event.target.value as RiskSeverity)}
                    >
                      {severityOptions.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Status</label>
                    <select
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={riskStatus}
                      onChange={(event) => setRiskStatus(event.target.value as RiskStatus)}
                    >
                      {statusOptions.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Analysis ID</label>
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={analysisId}
                      onChange={(event) => setAnalysisId(event.target.value)}
                      placeholder="Optional"
                    />
                  </div>
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Saving...' : 'Log risk'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Risk list</CardTitle>
            <CardDescription>Latest risks and mitigations.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {loading ? <p className="text-sm text-muted">Loading risks...</p> : null}
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            {!loading && !error && risks.length === 0 ? (
              <p className="text-sm text-muted">No risks recorded.</p>
            ) : null}
            <div className="grid gap-3">
              {risks.map((risk) => (
                <Card key={risk.id}>
                  <CardHeader>
                    <CardTitle>{risk.title}</CardTitle>
                    <CardDescription>{risk.description || 'No description'}</CardDescription>
                  </CardHeader>
                  <CardContent className="flex flex-wrap items-center gap-2">
                    <Badge>{risk.status}</Badge>
                    <Badge variant={risk.severity === 'CRITICAL' ? 'danger' : 'warning'}>
                      {risk.severity}
                    </Badge>
                    {risk.analysisId ? (
                      <span className="text-xs text-muted">Analysis: {risk.analysisId}</span>
                    ) : null}
                    {risk.resolvedAt ? (
                      <span className="text-xs text-muted">Resolved: {risk.resolvedAt}</span>
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
