'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { canEditAnalysis, canSubmitAnalysis } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';
import type { Analysis, Hypothesis } from '@/lib/types';

const analysisTypes = ['LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY'] as const;

type ValidationState = {
  evidenceLinked: boolean;
  riskAssessed: boolean;
  nextStepsDefined: boolean;
  stakeholderNotified: boolean;
};

const defaultValidation: ValidationState = {
  evidenceLinked: false,
  riskAssessed: false,
  nextStepsDefined: false,
  stakeholderNotified: false,
};

export default function AnalysisDetailPage({ params }: { params: { id: string } }) {
  const router = useRouter();
  const { user, accessToken, status } = useSession();
  const [analysis, setAnalysis] = useState<Analysis | null>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [symptoms, setSymptoms] = useState('');
  const [signals, setSignals] = useState('');
  const [hypotheses, setHypotheses] = useState<Hypothesis[]>([]);
  const [newHypothesis, setNewHypothesis] = useState('');
  const [newConfidence, setNewConfidence] = useState(50);
  const [newEvidence, setNewEvidence] = useState('');
  const [validation, setValidation] = useState<ValidationState>(defaultValidation);

  const role = user?.role ?? null;
  const editable = analysis ? canEditAnalysis(role, analysis.status) : false;

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
      const data = response.data;
      setAnalysis(data);
      setSymptoms(Array.isArray(data.symptoms) ? data.symptoms.join('\n') : '');
      setSignals(Array.isArray(data.signals) ? data.signals.join('\n') : '');
      setHypotheses(Array.isArray(data.hypotheses) ? data.hypotheses : []);
      setLoading(false);
    };

    void loadAnalysis();
  }, [status, accessToken, params.id]);

  const readinessScore = useMemo(() => {
    const symptomsReady = symptoms.trim().length > 0;
    const signalsReady = signals.trim().length > 0;
    const hypothesesReady = hypotheses.length > 0;
    const validationReady = Object.values(validation).every(Boolean);
    const score = [symptomsReady, signalsReady, hypothesesReady, validationReady].filter(Boolean).length * 25;
    return score;
  }, [symptoms, signals, hypotheses, validation]);

  const canSubmit = analysis ? canSubmitAnalysis(role, { ...analysis, readinessScore }) : false;

  const handleSave = async () => {
    if (!analysis || !accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Analysis>(
      'PUT',
      `/analyses/${analysis.id}`,
      {
        symptoms: symptoms.split('\n').map((item) => item.trim()).filter(Boolean),
        signals: signals.split('\n').map((item) => item.trim()).filter(Boolean),
        hypotheses,
        readinessScore,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to save analysis.');
      return;
    }
    setAnalysis(response.data);
  };

  const handleSubmit = async () => {
    if (!analysis || !accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Analysis>('POST', `/analyses/${analysis.id}/submit`, undefined, accessToken);

    setSaving(false);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to submit analysis.');
      return;
    }
    setAnalysis(response.data);
    router.push('/analysis');
  };

  const handleAddHypothesis = () => {
    if (!newHypothesis.trim()) return;
    const hypothesis: Hypothesis = {
      text: newHypothesis.trim(),
      confidence: Number(newConfidence),
      evidence: newEvidence.split('\n').map((item) => item.trim()).filter(Boolean),
    };
    setHypotheses((prev) => [...prev, hypothesis]);
    setNewHypothesis('');
    setNewEvidence('');
    setNewConfidence(50);
  };

  if (status !== 'authenticated') {
    return (
      <AppShell>
        <Card>
          <CardHeader>
            <CardTitle>Sign in required</CardTitle>
            <CardDescription>Please sign in to view the analysis.</CardDescription>
          </CardHeader>
        </Card>
      </AppShell>
    );
  }

  if (role === 'OWNER') {
    return (
      <AppShell>
        <Card>
          <CardHeader>
            <CardTitle>Owner access restricted</CardTitle>
            <CardDescription>Owners cannot view raw analyses.</CardDescription>
          </CardHeader>
        </Card>
      </AppShell>
    );
  }

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Analysis Workbench</h1>
          <p className="text-sm text-muted">Workflow state drives what can be edited.</p>
        </div>

        {loading ? <p className="text-sm text-muted">Loading analysis...</p> : null}
        {error ? <p className="text-sm text-rose-600">{error}</p> : null}

        {analysis ? (
          <Card>
            <CardHeader>
              <CardTitle>Analysis {analysis.id}</CardTitle>
              <CardDescription>Task {analysis.taskId}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center gap-2">
              <Badge>{analysis.status}</Badge>
              <Badge variant={readinessScore >= 75 ? 'success' : 'warning'}>Readiness {readinessScore}%</Badge>
              <span className="text-xs text-muted">Type: {analysis.analysisType ?? analysisTypes[0]}</span>
            </CardContent>
          </Card>
        ) : null}

        {analysis ? (
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Symptoms</CardTitle>
                <CardDescription>{editable ? 'Editable' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent>
                <textarea
                  className="min-h-[140px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                  value={symptoms}
                  onChange={(event) => setSymptoms(event.target.value)}
                  disabled={!editable}
                />
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Signals</CardTitle>
                <CardDescription>{editable ? 'Editable' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent>
                <textarea
                  className="min-h-[140px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                  value={signals}
                  onChange={(event) => setSignals(event.target.value)}
                  disabled={!editable}
                />
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Hypotheses</CardTitle>
                <CardDescription>{editable ? 'Editable' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {hypotheses.length === 0 ? <p className="text-sm text-muted">No hypotheses added.</p> : null}
                <div className="space-y-2">
                  {hypotheses.map((item, index) => (
                    <div key={`${item.text}-${index}`} className="rounded-lg border border-slate-200 p-3">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium">{item.text}</p>
                        <span className="text-xs text-muted">{item.confidence}%</span>
                      </div>
                      {item.evidence?.length ? (
                        <ul className="mt-2 list-disc pl-4 text-xs text-muted">
                          {item.evidence.map((entry) => (
                            <li key={entry}>{entry}</li>
                          ))}
                        </ul>
                      ) : null}
                    </div>
                  ))}
                </div>
                {editable ? (
                  <div className="space-y-2 rounded-lg border border-slate-200 p-3">
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      placeholder="Hypothesis"
                      value={newHypothesis}
                      onChange={(event) => setNewHypothesis(event.target.value)}
                    />
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      type="number"
                      min={0}
                      max={100}
                      value={newConfidence}
                      onChange={(event) => setNewConfidence(Number(event.target.value))}
                    />
                    <textarea
                      className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      placeholder="Evidence (one per line)"
                      value={newEvidence}
                      onChange={(event) => setNewEvidence(event.target.value)}
                    />
                    <Button size="sm" onClick={handleAddHypothesis} disabled={!editable}>
                      Add hypothesis
                    </Button>
                  </div>
                ) : null}
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Validation checklist</CardTitle>
                <CardDescription>{editable ? 'Complete to unlock submit' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2 text-sm text-muted">
                {Object.entries(validation).map(([key, value]) => (
                  <label key={key} className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={value}
                      onChange={(event) =>
                        setValidation((prev) => ({ ...prev, [key]: event.target.checked }))
                      }
                      disabled={!editable}
                    />
                    <span>
                      {key === 'evidenceLinked' && 'Evidence linked to hypotheses'}
                      {key === 'riskAssessed' && 'Risk assessed'}
                      {key === 'nextStepsDefined' && 'Next steps defined'}
                      {key === 'stakeholderNotified' && 'Stakeholders notified'}
                    </span>
                  </label>
                ))}
              </CardContent>
            </Card>
          </div>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Workflow actions</CardTitle>
            <CardDescription>Actions are enforced by backend state.</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            <Button onClick={handleSave} disabled={!editable || saving}>
              Save draft
            </Button>
            <Button onClick={handleSubmit} disabled={!canSubmit || saving}>
              Submit for review
            </Button>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
