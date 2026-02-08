'use client';

import { useEffect, useMemo, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { canEditAnalysis, canSubmitAnalysis } from '@/lib/auth';
import { useSession } from '@/hooks/useSession';
import type {
  Analysis,
  AiOutput,
  DependencyImpact,
  EnvironmentContext,
  Hypothesis,
  RiskClassification,
  TimelineEvents,
} from '@/lib/types';

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

export default function AnalysisDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string | string[] }>();
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
  const [environmentContext, setEnvironmentContext] = useState<EnvironmentContext>({});
  const [timelineEvents, setTimelineEvents] = useState<TimelineEvents>({});
  const [dependencyImpact, setDependencyImpact] = useState<DependencyImpact>({});
  const [riskClassification, setRiskClassification] = useState<RiskClassification>({});
  const [aiOutputs, setAiOutputs] = useState<AiOutput[]>([]);
  const [aiLoading, setAiLoading] = useState(false);
  const [aiError, setAiError] = useState<string | null>(null);

  const role = user?.role ?? null;
  const editable = analysis ? canEditAnalysis(role, analysis.status) : false;

  const parseLines = (value: string) => value.split('\n').map((item) => item.trim()).filter(Boolean);
  const joinLines = (items?: string[]) => (items?.length ? items.join('\n') : '');

  const analysisId = useMemo(() => {
    if (!params?.id) return null;
    return Array.isArray(params.id) ? params.id[0] : params.id;
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
      const data = response.data;
      setAnalysis(data);
      setSymptoms(Array.isArray(data.symptoms) ? data.symptoms.join('\n') : '');
      setSignals(Array.isArray(data.signals) ? data.signals.join('\n') : '');
      setHypotheses(Array.isArray(data.hypotheses) ? data.hypotheses : []);
      setEnvironmentContext(data.environmentContext ?? {});
      setTimelineEvents(data.timelineEvents ?? {});
      setDependencyImpact(data.dependencyImpact ?? {});
      setRiskClassification(data.riskClassification ?? {});
      const aiResponse = await apiRequest<AiOutput[]>(
        'GET',
        `/analyses/${analysisId}/ai/outputs`,
        undefined,
        accessToken,
      );
      if (aiResponse.success && aiResponse.data) {
        setAiOutputs(aiResponse.data);
      }
      setLoading(false);
    };

    void loadAnalysis();
  }, [status, accessToken, analysisId]);

  const readinessScore = useMemo(() => {
    const symptomsReady = symptoms.trim().length > 0;
    const signalsReady = signals.trim().length > 0;
    const hypothesesReady = hypotheses.length > 0;
    const validationReady = Object.values(validation).every(Boolean);
    const environmentReady = Boolean(
      environmentContext.cloudProvider &&
      environmentContext.region &&
      environmentContext.serviceType &&
      environmentContext.deploymentVersion,
    );
    const timelineReady = Boolean(
      (timelineEvents.deployments?.length ?? 0) > 0 ||
      (timelineEvents.configChanges?.length ?? 0) > 0 ||
      (timelineEvents.trafficSpikes?.length ?? 0) > 0 ||
      (timelineEvents.alerts?.length ?? 0) > 0,
    );
    const dependencyReady = Boolean(
      (dependencyImpact.upstreamServices?.length ?? 0) > 0 ||
      (dependencyImpact.downstreamServices?.length ?? 0) > 0 ||
      (dependencyImpact.sharedInfrastructure?.length ?? 0) > 0,
    );
    const riskReady = Boolean(
      riskClassification.customerImpact &&
      riskClassification.slaImpact &&
      riskClassification.severityLevel,
    );
    const sections = [
      symptomsReady,
      signalsReady,
      hypothesesReady,
      environmentReady,
      timelineReady,
      dependencyReady,
      riskReady,
      validationReady,
    ];
    const score = Math.round((sections.filter(Boolean).length / sections.length) * 100);
    return score;
  }, [
    symptoms,
    signals,
    hypotheses,
    validation,
    environmentContext,
    timelineEvents,
    dependencyImpact,
    riskClassification,
  ]);

  const canSubmit = analysis ? canSubmitAnalysis(role, { ...analysis, readinessScore }) : false;

  const handleSave = async () => {
    if (!analysis || !accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Analysis>(
      'PUT',
      `/analyses/${analysis.id}`,
      {
        symptoms: parseLines(symptoms),
        signals: parseLines(signals),
        hypotheses,
        environmentContext,
        timelineEvents,
        dependencyImpact,
        riskClassification,
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

  const handleGenerateAiHypotheses = async () => {
    if (!analysis || !accessToken) return;
    setAiLoading(true);
    setAiError(null);
    const response = await apiRequest<AiOutput>(
      'POST',
      `/analyses/${analysis.id}/ai/hypotheses`,
      undefined,
      accessToken,
    );
    setAiLoading(false);
    const output = response.data;
    if (!response.success || !output) {
      setAiError(response.error || 'Failed to generate AI hypotheses.');
      return;
    }
    setAiOutputs((prev) => [output, ...prev]);
  };

  const updateAiOutputStatus = async (outputId: string, status: 'ACCEPTED' | 'REJECTED' | 'EDITED') => {
    if (!accessToken) return;
    const response = await apiRequest<AiOutput>(
      'PATCH',
      `/ai/outputs/${outputId}`,
      { status },
      accessToken,
    );
    if (!response.success) {
      setAiError(response.error || 'Failed to update AI output status.');
      return;
    }
    setAiOutputs((prev) => prev.map((item) => (item.id === outputId ? { ...item, status } : item)));
  };

  const handleAcceptAiOutput = async (output: AiOutput) => {
    const aiHypotheses = (output.payload as { hypotheses?: Hypothesis[] })?.hypotheses ?? [];
    if (aiHypotheses.length === 0) return;
    setHypotheses(aiHypotheses);
    await updateAiOutputStatus(output.id, 'ACCEPTED');
  };

  const handleRejectAiOutput = async (output: AiOutput) => {
    await updateAiOutputStatus(output.id, 'REJECTED');
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
              <CardTitle>{analysis.title ? analysis.title : `Analysis ${analysis.id}`}</CardTitle>
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
            <Card>
              <CardHeader>
                <CardTitle>Environment context</CardTitle>
                <CardDescription>{editable ? 'Capture runtime environment' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid gap-3 md:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Cloud provider</label>
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      value={environmentContext.cloudProvider ?? ''}
                      onChange={(event) =>
                        setEnvironmentContext((prev) => ({ ...prev, cloudProvider: event.target.value }))
                      }
                      disabled={!editable}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Region</label>
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      value={environmentContext.region ?? ''}
                      onChange={(event) => setEnvironmentContext((prev) => ({ ...prev, region: event.target.value }))}
                      disabled={!editable}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Service type</label>
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      value={environmentContext.serviceType ?? ''}
                      onChange={(event) =>
                        setEnvironmentContext((prev) => ({ ...prev, serviceType: event.target.value }))
                      }
                      disabled={!editable}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Deployment version</label>
                    <input
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                      value={environmentContext.deploymentVersion ?? ''}
                      onChange={(event) =>
                        setEnvironmentContext((prev) => ({ ...prev, deploymentVersion: event.target.value }))
                      }
                      disabled={!editable}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Timeline of events</CardTitle>
                <CardDescription>{editable ? 'Track key signals over time' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Deployments</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(timelineEvents.deployments)}
                    onChange={(event) =>
                      setTimelineEvents((prev) => ({ ...prev, deployments: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Config changes</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(timelineEvents.configChanges)}
                    onChange={(event) =>
                      setTimelineEvents((prev) => ({ ...prev, configChanges: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Traffic spikes</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(timelineEvents.trafficSpikes)}
                    onChange={(event) =>
                      setTimelineEvents((prev) => ({ ...prev, trafficSpikes: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Alerts</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(timelineEvents.alerts)}
                    onChange={(event) =>
                      setTimelineEvents((prev) => ({ ...prev, alerts: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Dependency impact</CardTitle>
                <CardDescription>{editable ? 'Capture service dependencies' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Upstream services</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(dependencyImpact.upstreamServices)}
                    onChange={(event) =>
                      setDependencyImpact((prev) => ({ ...prev, upstreamServices: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Downstream services</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(dependencyImpact.downstreamServices)}
                    onChange={(event) =>
                      setDependencyImpact((prev) => ({ ...prev, downstreamServices: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Shared infrastructure</label>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={joinLines(dependencyImpact.sharedInfrastructure)}
                    onChange={(event) =>
                      setDependencyImpact((prev) => ({ ...prev, sharedInfrastructure: parseLines(event.target.value) }))
                    }
                    disabled={!editable}
                  />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Risk classification</CardTitle>
                <CardDescription>{editable ? 'Classify impact and severity' : 'Locked'}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Customer impact</label>
                  <input
                    className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={riskClassification.customerImpact ?? ''}
                    onChange={(event) =>
                      setRiskClassification((prev) => ({ ...prev, customerImpact: event.target.value }))
                    }
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">SLA impact</label>
                  <input
                    className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                    value={riskClassification.slaImpact ?? ''}
                    onChange={(event) => setRiskClassification((prev) => ({ ...prev, slaImpact: event.target.value }))}
                    disabled={!editable}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Severity level</label>
                  <select
                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={riskClassification.severityLevel ?? ''}
                    onChange={(event) =>
                      setRiskClassification((prev) => ({
                        ...prev,
                        severityLevel: event.target.value as RiskClassification['severityLevel'],
                      }))
                    }
                    disabled={!editable}
                  >
                    <option value="">Select severity</option>
                    <option value="LOW">Low</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="HIGH">High</option>
                    <option value="CRITICAL">Critical</option>
                  </select>
                </div>
              </CardContent>
            </Card>
          </div>
        ) : null}

        {analysis ? (
          <Card>
            <CardHeader>
              <CardTitle>AI hypotheses</CardTitle>
              <CardDescription>Generate and curate AI suggestions.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex flex-wrap items-center gap-2">
                <Button size="sm" onClick={handleGenerateAiHypotheses} disabled={!editable || aiLoading}>
                  {aiLoading ? 'Generating...' : 'Generate AI hypotheses'}
                </Button>
                {aiError ? <span className="text-xs text-rose-600">{aiError}</span> : null}
              </div>
              {aiOutputs.length === 0 ? (
                <p className="text-sm text-muted">No AI output yet.</p>
              ) : (
                <div className="space-y-3">
                  {aiOutputs.slice(0, 3).map((output) => {
                    const hypothesesPayload = (output.payload as { hypotheses?: Hypothesis[] })?.hypotheses ?? [];
                    return (
                      <div key={output.id} className="rounded-lg border border-slate-200 p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <p className="text-xs uppercase text-muted">Output {output.status}</p>
                          {editable ? (
                            <div className="flex gap-2">
                              <Button size="sm" variant="secondary" onClick={() => handleAcceptAiOutput(output)}>
                                Accept
                              </Button>
                              <Button size="sm" variant="ghost" onClick={() => handleRejectAiOutput(output)}>
                                Reject
                              </Button>
                            </div>
                          ) : null}
                        </div>
                        {hypothesesPayload.length === 0 ? (
                          <p className="text-sm text-muted">No hypotheses returned.</p>
                        ) : (
                          <div className="mt-3 space-y-2">
                            {hypothesesPayload.map((item, index) => (
                              <div key={`${output.id}-${index}`} className="rounded-md border border-slate-200 p-2">
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
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>
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
