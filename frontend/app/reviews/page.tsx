'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Analysis } from '@/lib/types';

export default function ReviewsPage() {
  const { user, accessToken, status } = useSession();
  const [analyses, setAnalyses] = useState<Analysis[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const role = user?.role ?? null;

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken || role !== 'MANAGER') return;
    const loadQueue = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Analysis[]>('GET', '/analyses', undefined, accessToken);
      if (!response.success || !response.data) {
        setError(response.error || 'Unable to load review queue.');
        setAnalyses([]);
        setLoading(false);
        return;
      }
      setAnalyses(response.data);
      setLoading(false);
    };

    void loadQueue();
  }, [status, accessToken, role]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Review Queue</h1>
          <p className="text-sm text-muted">Submitted analyses awaiting decision.</p>
        </div>

        {role !== 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Manager access required</CardTitle>
              <CardDescription>Only managers can review submissions.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Queue</CardTitle>
              <CardDescription>Analyses awaiting review.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? <p className="text-sm text-muted">Loading queue...</p> : null}
              {error ? <p className="text-sm text-rose-600">{error}</p> : null}
              {!loading && !error && analyses.length === 0 ? (
                <p className="text-sm text-muted">No submissions found.</p>
              ) : null}
              <div className="grid gap-3">
                {analyses.map((analysis) => (
                  <Card key={analysis.id}>
                    <CardHeader>
                      <CardTitle>{analysis.title ? analysis.title : `Analysis ${analysis.id}`}</CardTitle>
                      <CardDescription>Task {analysis.taskId}</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-2">
                      <Badge>{analysis.status}</Badge>
                      <Link className="text-sm text-ink underline" href={`/reviews/${analysis.id}`}>
                        Review
                      </Link>
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
