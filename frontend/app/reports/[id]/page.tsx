'use client';

import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Report } from '@/lib/types';
import { useParams } from 'next/navigation';

export default function ReportDetailPage() {
  const { accessToken, status } = useSession();
  const params = useParams<{ id: string | string[] }>();
  const [report, setReport] = useState<Report | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reportId = useMemo(() => {
    if (!params?.id) return null;
    return Array.isArray(params.id) ? params.id[0] : params.id;
  }, [params]);

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    if (!reportId) return;
    const loadReport = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Report>('GET', `/reports/${reportId}`, undefined, accessToken);
      if (!response.success || !response.data) {
        setError(response.error || 'Unable to load report.');
        setReport(null);
        setLoading(false);
        return;
      }
      setReport(response.data);
      setLoading(false);
    };

    void loadReport();
  }, [status, accessToken, reportId]);

  const analysisId = report?.analysisId ?? report?.analysis_id ?? 'Unknown';
  const createdAt = report?.createdAt ?? report?.created_at ?? 'Unknown';
  const legacyReport = report as Report & {
    executive_summary?: string;
    root_cause?: string;
    prevention_steps?: string;
    ai_assisted?: number | boolean;
  };
  const executiveSummary =
    report?.executiveSummary ?? legacyReport?.executive_summary ?? report?.summary ?? 'No summary provided.';
  const rootCause = report?.rootCause ?? legacyReport?.root_cause ?? 'No root cause provided.';
  const impact = report?.impact ?? 'No impact provided.';
  const resolution = report?.resolution ?? 'No resolution provided.';
  const preventionSteps = report?.preventionSteps ?? legacyReport?.prevention_steps ?? 'No prevention steps provided.';
  const aiAssistedFlag = report?.aiAssisted ?? legacyReport?.ai_assisted ?? false;

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Report</h1>
          <p className="text-sm text-muted">Printable executive summary.</p>
        </div>

        {loading ? <p className="text-sm text-muted">Loading report...</p> : null}
        {error ? <p className="text-sm text-rose-600">{error}</p> : null}

        {report ? (
          <Card>
            <CardHeader>
              <CardTitle>Report {report.id}</CardTitle>
              <CardDescription>Analysis {analysisId}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-xs uppercase text-muted">Created</p>
                <p className="text-sm text-ink">{createdAt}</p>
              </div>
              {aiAssistedFlag ? (
                <div>
                  <p className="text-xs uppercase text-muted">AI assisted</p>
                  <p className="text-sm text-ink">Yes</p>
                </div>
              ) : null}
              <div>
                <p className="text-xs uppercase text-muted">Executive Summary</p>
                <p className="text-sm text-ink whitespace-pre-line">{executiveSummary}</p>
              </div>
              <div>
                <p className="text-xs uppercase text-muted">Root cause</p>
                <p className="text-sm text-ink whitespace-pre-line">{rootCause}</p>
              </div>
              <div>
                <p className="text-xs uppercase text-muted">Impact</p>
                <p className="text-sm text-ink whitespace-pre-line">{impact}</p>
              </div>
              <div>
                <p className="text-xs uppercase text-muted">Resolution</p>
                <p className="text-sm text-ink whitespace-pre-line">{resolution}</p>
              </div>
              <div>
                <p className="text-xs uppercase text-muted">Prevention steps</p>
                <p className="text-sm text-ink whitespace-pre-line">{preventionSteps}</p>
              </div>
              <Button onClick={() => window.print()}>Print</Button>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
