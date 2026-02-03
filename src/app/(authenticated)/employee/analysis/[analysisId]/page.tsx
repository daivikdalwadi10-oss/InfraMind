import React from 'react';
import { getAnalysisForEmployee } from '@/src/app/actions';
import PageHeader from '@/src/components/ui/PageHeader';
import GlassCard from '@/src/components/ui/GlassCard';
import AnalysisWorkbenchClient from './AnalysisWorkbenchClient';
import type { Analysis } from '@/src/lib/types';

type AnalysisPageProps = {
  params: { analysisId: string };
  searchParams?: Record<string, string | string[] | undefined> | Promise<Record<string, string | string[] | undefined>>;
};

export default async function AnalysisPage({ params }: AnalysisPageProps) {
  const analysisId = params.analysisId;

  let analysis: Awaited<ReturnType<typeof getAnalysisForEmployee>> | null = null;
  let error: string | null = null;
  try {
    analysis = await getAnalysisForEmployee(analysisId);
  } catch (err) {
    error = String(err);
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Analysis Workbench" subtitle="Capture symptoms, signals, and hypotheses with AI assistance." />
      {error && <GlassCard><p className="text-sm text-rose-400">{error}</p></GlassCard>}
      {analysis && <AnalysisWorkbenchClient analysis={analysis as Analysis & { id?: string }} />}
    </div>
  );
}
