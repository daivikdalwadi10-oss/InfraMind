import React from 'react';
import { getReportForOwner, listReportsForOwner } from '@/src/app/actions';
import PageHeader from '@/src/components/ui/PageHeader';
import GlassCard from '@/src/components/ui/GlassCard';
import TaskList from '@/src/components/ui/TaskList';

type ReportData = {
  id?: string;
  analysisId?: string;
  summary?: string;
  createdAt?: { seconds: number; nanoseconds?: number };
};

type OwnerSearchParams = { reportId?: string; limit?: string };
type OwnerPageProps = {
  searchParams?: OwnerSearchParams | Promise<OwnerSearchParams>;
};

export default async function OwnerPage({ searchParams }: OwnerPageProps) {
  const resolved = await (searchParams ?? {});
  const reportId = resolved.reportId ?? '';
  const limit = resolved.limit ?? '';
  let report: Awaited<ReturnType<typeof getReportForOwner>> | null = null;
  let reports: Awaited<ReturnType<typeof listReportsForOwner>> = [];
  let error: string | null = null;

  try {
    if (reportId) {
      report = await getReportForOwner(reportId);
    }
    const parsedLimit = Number.isNaN(Number(limit)) ? undefined : Number(limit);
    reports = await listReportsForOwner({ limit: parsedLimit });
  } catch (err) {
    error = String(err);
  }

  return (
    <div className="space-y-8">
      <PageHeader title="Overview" subtitle="Owners can view generated reports only." />

      <GlassCard>
        <h2 className="text-sm font-semibold text-white">Find report</h2>
        <form method="get" className="mt-4 grid gap-3 md:grid-cols-2">
          <input
            name="reportId"
            defaultValue={reportId}
            placeholder="Report ID"
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
          />
          <button type="submit" className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400">
            View Report
          </button>
        </form>

        {error && <p className="mt-3 text-sm text-rose-400">{error}</p>}

        {report && (
          <div className="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
            <div className="text-xs uppercase tracking-wide text-slate-500">Report {(report as ReportData).id}</div>
            <div className="mt-2 text-sm text-slate-300">Analysis ID: {(report as ReportData).analysisId}</div>
            <div className="mt-3 text-sm font-semibold text-white">Summary</div>
            <p className="mt-2 text-sm text-slate-200">
              {(report as ReportData).summary ?? 'No summary available.'}
            </p>
          </div>
        )}
      </GlassCard>

      <TaskList
        title="Reports"
        items={(reports as ReportData[]).map((item) => ({
          id: item.id ?? '',
          title: `Report ${item.id}`,
          description: `Analysis ${item.analysisId}`,
          status: 'APPROVED',
          meta: `Created ${new Date((item.createdAt?.seconds ?? 0) * 1000).toLocaleDateString()}`,
        }))}
        emptyMessage="No reports found."
      />

      <GlassCard>
        <h2 className="text-sm font-semibold text-white">Filter list</h2>
        <form method="get" className="mt-4 grid gap-3 md:grid-cols-2">
          <input
            name="limit"
            defaultValue={limit}
            placeholder="Limit (1-50)"
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
          />
          <button type="submit" className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400">
            Refresh
          </button>
        </form>
      </GlassCard>
    </div>
  );
}
