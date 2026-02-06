'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { canReviewAnalysis } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';
import type { Analysis } from '@/lib/types';

export default function ReviewDetailPage({ params }: { params: { id: string } }) {
  const router = useRouter();
  const { user, accessToken, status } = useSession();
  const [analysis, setAnalysis] = useState<Analysis | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [feedback, setFeedback] = useState('');
  const [saving, setSaving] = useState(false);

  const role = user?.role ?? null;
  const canReview = analysis ? canReviewAnalysis(role, analysis.status) : false;

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken) return;
    const loadAnalysis = async () => {
      setLoading(true);
      setError(null);
      const response = await apiRequest<Analysis>('GET', `/analyses/${params.id}`, undefined, accessToken);
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
  }, [status, accessToken, params.id]);

  const handleDecision = async (decision: 'APPROVE' | 'REJECT') => {
    if (!analysis || !accessToken) return;
    if (decision === 'REJECT' && !feedback.trim()) {
      setError('Feedback is required when requesting changes.');
      return;
    }
    setSaving(true);
    setError(null);

    const response = await apiRequest<Analysis>(
      'POST',
      `/analyses/${analysis.id}/review`,
      { decision, feedback: feedback.trim() || null },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to submit review.');
      return;
    }

    setAnalysis(response.data);
    if (response.data.status === 'APPROVED') {
      router.push(`/reports/generate/${analysis.id}`);
      return;
    }
    router.push('/reviews');
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Review Analysis</h1>
          <p className="text-sm text-muted">Read-only view with decision controls.</p>
        </div>

        {loading ? <p className="text-sm text-muted">Loading analysis...</p> : null}
        {error ? <p className="text-sm text-rose-600">{error}</p> : null}

        {analysis ? (
          <Card>
            <CardHeader>
              <CardTitle>Analysis {analysis.id}</CardTitle>
              <CardDescription>Task {analysis.taskId}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <Badge>{analysis.status}</Badge>
              <div className="grid gap-3 md:grid-cols-2">
                <div>
                  <p className="text-xs uppercase text-muted">Symptoms</p>
                  <pre className="whitespace-pre-wrap text-sm text-ink">
                    {(analysis.symptoms || []).join('\n') || 'No symptoms'}
                  </pre>
                </div>
                <div>
                  <p className="text-xs uppercase text-muted">Signals</p>
                  <pre className="whitespace-pre-wrap text-sm text-ink">
                    {(analysis.signals || []).join('\n') || 'No signals'}
                  </pre>
                </div>
                <div className="md:col-span-2">
                  <p className="text-xs uppercase text-muted">Hypotheses</p>
                  <pre className="whitespace-pre-wrap text-sm text-ink">
                    {(analysis.hypotheses || []).map((item) => item.text).join('\n') || 'No hypotheses'}
                  </pre>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        {role === 'MANAGER' ? (
          <Card>
            <CardHeader>
              <CardTitle>Decision</CardTitle>
              <CardDescription>Approve or request changes.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <textarea
                className="min-h-[120px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="Feedback (required for changes)"
                value={feedback}
                onChange={(event) => setFeedback(event.target.value)}
                disabled={!canReview}
              />
              <div className="flex flex-wrap gap-2">
                <Button onClick={() => handleDecision('APPROVE')} disabled={!canReview || saving}>
                  Approve
                </Button>
                <Button
                  variant="secondary"
                  onClick={() => handleDecision('REJECT')}
                  disabled={!canReview || saving}
                >
                  Request changes
                </Button>
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
