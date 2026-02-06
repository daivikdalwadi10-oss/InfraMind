'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { canGenerateReport } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';
import type { Analysis, Report } from '@/lib/types';

export default function ReportGenerationPage({ params }: { params: { analysisId: string } }) {
  const router = useRouter();
  const { user, accessToken, status } = useSession();
  const [analysis, setAnalysis] = useState<Analysis | null>(null);
  const [summary, setSummary] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const role = user?.role ?? null;

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    const loadAnalysis = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Analysis>('GET', `/analyses/${params.analysisId}`, undefined, accessToken);
      if (!response.success || !response.data) {
        setError(response.error || 'Unable to load analysis.');
        setAnalysis(null);
        setLoading(false);
        return;
      }
      setAnalysis(response.data);
      setLoading(false);
    };

    void loadAnalysis();
  }, [status, accessToken, params.analysisId]);

  const canGenerate = analysis ? canGenerateReport(role, analysis.status) : false;

  const handleGenerate = async () => {
    if (!analysis || !accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Report>(
      'POST',
      '/reports',
      { analysisId: analysis.id, summary },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to generate report.');
      return;
    }

    router.push(`/reports/${response.data.id}`);
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Report Generation</h1>
          <p className="text-sm text-muted">Generate executive summary after approval.</p>
        </div>

        {loading ? <p className="text-sm text-muted">Loading analysis...</p> : null}
        {error ? <p className="text-sm text-rose-600">{error}</p> : null}

        {role !== 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Manager access required</CardTitle>
              <CardDescription>Only managers can generate reports.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {analysis && role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Analysis {analysis.id}</CardTitle>
              <CardDescription>Task {analysis.taskId}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center gap-2">
              <Badge>{analysis.status}</Badge>
              <span className="text-xs text-muted">Readiness {analysis.readinessScore ?? 0}%</span>
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Executive summary</CardTitle>
              <CardDescription>Summarize the approved analysis.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <textarea
                className="min-h-[200px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                value={summary}
                onChange={(event) => setSummary(event.target.value)}
                disabled={!canGenerate}
              />
              <Button onClick={handleGenerate} disabled={!canGenerate || saving}>
                Generate report
              </Button>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
