"use client";

import React from 'react';
import { useRouter } from 'next/navigation';
import {
  submitAnalysisForm,
  suggestHypothesesForm,
  updateAnalysisSectionForm,
  type ActionResult,
} from '@/src/app/actions';
import AnalysisSection from '@/src/components/ui/AnalysisSection';
import GlassCard from '@/src/components/ui/GlassCard';
import ReadinessMeter from '@/src/components/ui/ReadinessMeter';
import AIHintBox from '@/src/components/ui/AIHintBox';
import StatusBadge from '@/src/components/ui/StatusBadge';
import ConfirmDialog from '@/src/components/ui/ConfirmDialog';
import { useToast } from '@/src/components/ui/Toast';
import type { Analysis } from '@/src/lib/types';

const initialState: ActionResult = { ok: true, message: '' };

type AnalysisWorkbenchClientProps = {
  analysis: Analysis & { id?: string };
};

function SectionForm({
  title,
  subtitle,
  values,
  section,
  disabled,
  analysisId,
}: {
  title: string;
  subtitle: string;
  values: string[];
  section: 'symptoms' | 'signals' | 'hypotheses';
  disabled: boolean;
  analysisId: string;
}) {
  const { show } = useToast();
  const router = useRouter();
  const [pending, startTransition] = React.useTransition();

  return (
    <AnalysisSection title={title} subtitle={subtitle} disabled={disabled}>
      <form
        onSubmit={(event) => {
          event.preventDefault();
          const formData = new FormData(event.currentTarget);
          startTransition(async () => {
            const res = await updateAnalysisSectionForm(initialState, formData);
            show({
              title: res.ok ? 'Section updated' : 'Update failed',
              description: res.message,
              variant: res.ok ? 'success' : 'error',
            });
            if (res.ok) router.refresh();
          });
        }}
        className="space-y-3"
      >
        <input type="hidden" name="analysisId" value={analysisId} />
        <input type="hidden" name="section" value={section} />
        <textarea
          name="values"
          defaultValue={values.join('\n')}
          disabled={disabled}
          placeholder="One item per line"
          className="min-h-[140px] w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none disabled:cursor-not-allowed"
        />
        <div className="flex justify-end">
          <button
            type="submit"
            disabled={disabled || pending}
            className="rounded-xl bg-blue-500 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {pending ? 'Saving...' : 'Save section'}
          </button>
        </div>
      </form>
    </AnalysisSection>
  );
}

export default function AnalysisWorkbenchClient({ analysis }: AnalysisWorkbenchClientProps) {
  const { show } = useToast();
  const router = useRouter();
  const [suggesting, suggestTransition] = React.useTransition();
  const [savingHypotheses, saveTransition] = React.useTransition();
  const [submitting, submitTransition] = React.useTransition();
  const locked = !['DRAFT', 'NEEDS_CHANGES'].includes(analysis.status);

  const submit = () => {
    const formData = new FormData();
    if (analysis.id) formData.append('analysisId', analysis.id);
    submitTransition(async () => {
      const res = await submitAnalysisForm(initialState, formData);
      show({
        title: res.ok ? 'Analysis submitted' : 'Submission failed',
        description: res.message,
        variant: res.ok ? 'success' : 'error',
      });
      if (res.ok) router.refresh();
    });
  };

  return (
    <div className="space-y-6">
      <GlassCard className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="text-xs uppercase tracking-wide text-slate-500">Analysis {analysis.id}</div>
          <h2 className="mt-2 text-lg font-semibold text-white">Task {analysis.taskId}</h2>
          <p className="mt-1 text-xs text-slate-400">Type {analysis.analysisType}</p>
          <p className="mt-1 text-xs text-slate-400">
            Last updated {new Date(analysis.updatedAt.seconds * 1000).toLocaleString()}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <StatusBadge status={analysis.status} />
          <ConfirmDialog
            trigger={
              <button
                className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
                disabled={locked || submitting}
              >
                {submitting ? 'Submitting...' : 'Submit analysis'}
              </button>
            }
            title="Submit analysis"
            description="Submission is only allowed when readiness score is at least 75."
            confirmLabel="Submit"
            onConfirm={submit}
            disabled={locked || submitting}
          />
        </div>
      </GlassCard>

      {analysis.status === 'NEEDS_CHANGES' && analysis.managerFeedback && (
        <GlassCard>
          <h3 className="text-sm font-semibold text-white">Manager feedback</h3>
          <p className="mt-2 text-sm text-slate-200">{analysis.managerFeedback}</p>
        </GlassCard>
      )}

      <div className="grid gap-6 xl:grid-cols-3">
        <ReadinessMeter score={analysis.readinessScore} />
        <GlassCard>
          <h3 className="text-sm font-semibold text-white">Validation checklist</h3>
          <ul className="mt-3 space-y-2 text-xs text-slate-300">
            <li className="flex items-center justify-between">
              Symptoms captured
              <span className="text-slate-400">{analysis.symptoms.length} items</span>
            </li>
            <li className="flex items-center justify-between">
              Signals captured
              <span className="text-slate-400">{analysis.signals.length} items</span>
            </li>
            <li className="flex items-center justify-between">
              Hypotheses drafted
              <span className="text-slate-400">{analysis.hypotheses.length} items</span>
            </li>
          </ul>
        </GlassCard>
        <AIHintBox message="Use AI suggestions to accelerate hypothesis drafting when the evidence is strong." />
      </div>

      <div className="grid gap-6 xl:grid-cols-3">
        <SectionForm
          title="Symptoms"
          subtitle="Capture observed impact and pain points."
          values={analysis.symptoms}
          section="symptoms"
          disabled={locked}
          analysisId={analysis.id ?? ''}
        />
        <SectionForm
          title="Signals"
          subtitle="Document evidence, logs, and telemetry."
          values={analysis.signals}
          section="signals"
          disabled={locked}
          analysisId={analysis.id ?? ''}
        />
        <AnalysisSection
          title="Hypotheses"
          subtitle="Draft hypotheses and request AI assistance."
          disabled={locked}
          footer={
            <button
              onClick={() => {
                const formData = new FormData();
                if (analysis.id) formData.append('analysisId', analysis.id);
                suggestTransition(async () => {
                  const res = await suggestHypothesesForm(initialState, formData);
                  show({
                    title: res.ok ? 'AI suggestions added' : 'AI suggestion failed',
                    description: res.message,
                    variant: res.ok ? 'success' : 'error',
                  });
                  if (res.ok) router.refresh();
                });
              }}
              disabled={locked || suggesting}
              className="rounded-xl border border-blue-400/40 bg-blue-500/10 px-3 py-2 text-xs font-semibold text-blue-200 hover:bg-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {suggesting ? 'Generating...' : 'Get AI suggestions'}
            </button>
          }
        >
          <form
            onSubmit={(event) => {
              event.preventDefault();
              const formData = new FormData(event.currentTarget);
              saveTransition(async () => {
                const res = await updateAnalysisSectionForm(initialState, formData);
                show({
                  title: res.ok ? 'Section updated' : 'Update failed',
                  description: res.message,
                  variant: res.ok ? 'success' : 'error',
                });
                if (res.ok) router.refresh();
              });
            }}
            className="space-y-3"
          >
            <input type="hidden" name="analysisId" value={analysis.id ?? ''} />
            <input type="hidden" name="section" value="hypotheses" />
            <textarea
              name="values"
              defaultValue={analysis.hypotheses.join('\n')}
              disabled={locked}
              placeholder="One hypothesis per line"
              className="min-h-[140px] w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none disabled:cursor-not-allowed"
            />
            <div className="flex justify-end">
              <button
                type="submit"
                disabled={locked || savingHypotheses}
                className="rounded-xl bg-blue-500 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {savingHypotheses ? 'Saving...' : 'Save hypotheses'}
              </button>
            </div>
          </form>
        </AnalysisSection>
      </div>
    </div>
  );
}
