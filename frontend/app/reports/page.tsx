'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Report } from '@/lib/types';

export default function ReportsPage() {
  const { user, accessToken, status } = useSession();
  const [reports, setReports] = useState<Report[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const role = user?.role ?? null;

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    if (role !== 'MANAGER' && role !== 'OWNER') return;

    const loadReports = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Report[]>('GET', '/reports', undefined, accessToken);
      if (!response.success || !response.data) {
        setError(response.error || 'Unable to load reports.');
        setReports([]);
        setLoading(false);
        return;
      }
      setReports(response.data);
      setLoading(false);
    };

    void loadReports();
  }, [status, accessToken, role]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Reports</h1>
          <p className="text-sm text-muted">Executive summaries generated from approved analyses.</p>
        </div>

        {role !== 'MANAGER' && role !== 'OWNER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Access restricted</CardTitle>
              <CardDescription>Reports are available to managers and owners.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {role === 'MANAGER' || role === 'OWNER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Report list</CardTitle>
              <CardDescription>Open a report for details or printing.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? <p className="text-sm text-muted">Loading reports...</p> : null}
              {error ? <p className="text-sm text-rose-600">{error}</p> : null}
              {!loading && !error && reports.length === 0 ? (
                <p className="text-sm text-muted">No reports available.</p>
              ) : null}
              <div className="grid gap-3">
                {reports.map((report) => {
                  const analysisId = report.analysisId ?? report.analysis_id ?? 'Unknown';
                  const createdAt = report.createdAt ?? report.created_at ?? '';
                  return (
                    <Card key={report.id}>
                      <CardHeader>
                        <CardTitle>Report {report.id}</CardTitle>
                        <CardDescription>Analysis {analysisId}</CardDescription>
                      </CardHeader>
                      <CardContent className="flex flex-wrap items-center gap-2">
                        <Badge>Ready</Badge>
                        {createdAt ? <span className="text-xs text-muted">{createdAt}</span> : null}
                        <Link className="text-sm text-ink underline" href={`/reports/${report.id}`}>
                          View report
                        </Link>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
