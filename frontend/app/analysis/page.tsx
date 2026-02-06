'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Analysis } from '@/lib/types';

export default function AnalysesPage() {
  const { user, accessToken, status } = useSession();
  const [analyses, setAnalyses] = useState<Analysis[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const role = user?.role ?? null;

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    const loadAnalyses = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Analysis[]>('GET', '/analyses', undefined, accessToken);
      if (!response.success || !response.data) {
        setError(response.error || 'Unable to load analyses.');
        setAnalyses([]);
        setLoading(false);
        return;
      }
      setAnalyses(response.data);
      setLoading(false);
    };

    void loadAnalyses();
  }, [status, accessToken]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Analyses</h1>
          <p className="text-sm text-muted">Workflow-centric analysis records.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view analyses.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {role === 'OWNER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Owner access restricted</CardTitle>
              <CardDescription>Owners can only view reports.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {role !== 'OWNER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Analysis list</CardTitle>
              <CardDescription>Open a record to continue the workflow.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? <p className="text-sm text-muted">Loading analyses...</p> : null}
              {error ? <p className="text-sm text-rose-600">{error}</p> : null}
              {!loading && !error && analyses.length === 0 ? (
                <p className="text-sm text-muted">No analyses found.</p>
              ) : null}
              <div className="grid gap-3">
                {analyses.map((analysis) => (
                  <Card key={analysis.id}>
                    <CardHeader>
                      <CardTitle>Analysis {analysis.id}</CardTitle>
                      <CardDescription>Task: {analysis.taskId}</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-3">
                      <Badge>{analysis.status}</Badge>
                      <span className="text-xs text-muted">Readiness: {analysis.readinessScore ?? 0}%</span>
                      <Button asChild size="sm">
                        <Link href={`/analysis/${analysis.id}`}>Open workbench</Link>
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
