'use client';

import { useEffect, useMemo, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { canGenerateReport } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';
import type { AiOutput, Analysis, Report } from '@/lib/types';

export default function ReportGenerationPage() {
  const router = useRouter();
  const params = useParams<{ analysisId: string | string[] }>();
  const { user, accessToken, status } = useSession();
  const [analysis, setAnalysis] = useState<Analysis | null>(null);
  const [executiveSummary, setExecutiveSummary] = useState('');
  const [rootCause, setRootCause] = useState('');
  const [impact, setImpact] = useState('');
  const [resolution, setResolution] = useState('');
  const [preventionSteps, setPreventionSteps] = useState('');
  const [aiAssisted, setAiAssisted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [aiDraftLoading, setAiDraftLoading] = useState(false);
  const [aiDraftError, setAiDraftError] = useState<string | null>(null);

  const role = user?.role ?? null;

  const analysisId = useMemo(() => {
    if (!params?.analysisId) return null;
    return Array.isArray(params.analysisId) ? params.analysisId[0] : params.analysisId;
  }, [params]);

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    if (!analysisId) return;
    const loadAnalysis = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Analysis>('GET', `/analyses/${analysisId}`, undefined, accessToken);
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
  }, [status, accessToken, analysisId]);

  const canGenerate = analysis ? canGenerateReport(role, analysis.status) : false;

  const handleGenerate = async () => {
    if (!analysis || !accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Report>(
      'POST',
      '/reports',
      {
        analysisId: analysis.id,
        executiveSummary,
        rootCause,
        impact,
        resolution,
        preventionSteps,
        aiAssisted,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to generate report.');
      return;
    }

    router.push(`/reports/${response.data.id}`);
  };

  const handleGenerateAiDraft = async () => {
    if (!analysis || !accessToken) return;
    setAiDraftLoading(true);
    setAiDraftError(null);

    const response = await apiRequest<AiOutput>(
      'POST',
      `/analyses/${analysis.id}/ai/report-draft`,
      undefined,
      accessToken,
    );

    setAiDraftLoading(false);
    if (!response.success || !response.data) {
      setAiDraftError(response.error || 'Failed to generate AI report draft.');
      return;
    }

    const payload = response.data.payload as {
      executiveSummary?: string;
      rootCause?: string;
      impact?: string;
      resolution?: string;
      preventionSteps?: string;
    };

    setExecutiveSummary(payload.executiveSummary ?? '');
    setRootCause(payload.rootCause ?? '');
    setImpact(payload.impact ?? '');
    setResolution(payload.resolution ?? '');
    setPreventionSteps(payload.preventionSteps ?? '');
    setAiAssisted(true);

    await apiRequest('PATCH', `/ai/outputs/${response.data.id}`, { status: 'ACCEPTED' }, accessToken);
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
              <CardTitle>{analysis.title ? analysis.title : `Analysis ${analysis.id}`}</CardTitle>
              <CardDescription>Task {analysis.taskId}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center gap-2">
              <Badge>{analysis.status}</Badge>
              <span className="text-xs text-muted">Readiness {analysis.readinessScore ?? 0}%</span>
              <Button size="sm" variant="secondary" onClick={handleGenerateAiDraft} disabled={!canGenerate || aiDraftLoading}>
                {aiDraftLoading ? 'Drafting...' : 'Generate AI draft'}
              </Button>
              {aiDraftError ? <span className="text-xs text-rose-600">{aiDraftError}</span> : null}
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
                value={executiveSummary}
                onChange={(event) => setExecutiveSummary(event.target.value)}
                disabled={!canGenerate}
              />
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Root cause</CardTitle>
              <CardDescription>Primary fault and contributing factors.</CardDescription>
            </CardHeader>
            <CardContent>
              <textarea
                className="min-h-[160px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                value={rootCause}
                onChange={(event) => setRootCause(event.target.value)}
                disabled={!canGenerate}
              />
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Impact</CardTitle>
              <CardDescription>Customer and service impact details.</CardDescription>
            </CardHeader>
            <CardContent>
              <textarea
                className="min-h-[140px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                value={impact}
                onChange={(event) => setImpact(event.target.value)}
                disabled={!canGenerate}
              />
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Resolution</CardTitle>
              <CardDescription>Actions taken to restore service.</CardDescription>
            </CardHeader>
            <CardContent>
              <textarea
                className="min-h-[140px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                value={resolution}
                onChange={(event) => setResolution(event.target.value)}
                disabled={!canGenerate}
              />
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Prevention steps</CardTitle>
              <CardDescription>Prevent recurrence with concrete steps.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <textarea
                className="min-h-[140px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                value={preventionSteps}
                onChange={(event) => setPreventionSteps(event.target.value)}
                disabled={!canGenerate}
              />
              <label className="flex items-center gap-2 text-sm text-muted">
                <input
                  type="checkbox"
                  checked={aiAssisted}
                  onChange={(event) => setAiAssisted(event.target.checked)}
                  disabled={!canGenerate}
                />
                Mark as AI-assisted
              </label>
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
