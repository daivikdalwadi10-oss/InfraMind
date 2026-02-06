'use client';

import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Report } from '@/lib/types';

export default function ReportDetailPage({ params }: { params: { id: string } }) {
  const { accessToken, status } = useSession();
  const [report, setReport] = useState<Report | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    const loadReport = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Report>('GET', `/reports/${params.id}`, undefined, accessToken);
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
  }, [status, accessToken, params.id]);

  const analysisId = report?.analysisId ?? report?.analysis_id ?? 'Unknown';
  const createdAt = report?.createdAt ?? report?.created_at ?? 'Unknown';

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
              <div>
                <p className="text-xs uppercase text-muted">Executive Summary</p>
                <p className="text-sm text-ink whitespace-pre-line">{report.summary || 'No summary provided.'}</p>
              </div>
              <Button onClick={() => window.print()}>Print</Button>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
