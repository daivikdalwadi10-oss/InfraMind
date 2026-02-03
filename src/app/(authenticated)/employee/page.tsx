import React from 'react';
import Link from 'next/link';
import { listAnalysesForEmployee, listTasksForEmployee } from '@/src/app/actions';
import type { Analysis, Task } from '@/src/lib/types';
import EmployeeForms from './EmployeeForms';
import PageHeader from '@/src/components/ui/PageHeader';
import TaskList from '@/src/components/ui/TaskList';
import GlassCard from '@/src/components/ui/GlassCard';

type EmployeeSearchParams = { status?: string; limit?: string };
type EmployeePageProps = {
  searchParams?: EmployeeSearchParams | Promise<EmployeeSearchParams>;
};

export default async function EmployeePage({ searchParams }: EmployeePageProps) {
  const resolved = await (searchParams ?? {});
  const status = resolved.status ?? '';
  const limit = resolved.limit ?? '';
  let analyses: Awaited<ReturnType<typeof listAnalysesForEmployee>> = [];
  let tasks: Awaited<ReturnType<typeof listTasksForEmployee>> = [];
  let error: string | null = null;

  try {
    const parsedLimit = Number.isNaN(Number(limit)) ? undefined : Number(limit);
    analyses = await listAnalysesForEmployee({
      status: status ? (status as Analysis['status']) : undefined,
      limit: parsedLimit,
    });
    tasks = await listTasksForEmployee({ limit: parsedLimit });
  } catch (err) {
    error = String(err);
  }

  return (
    <div className="space-y-8">
      <PageHeader
        title="Overview"
        subtitle="Start analyses, request AI hypotheses, and submit when ready."
        actions={
          <Link
            href="/employee"
            className="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10"
          >
            Refresh
          </Link>
        }
      />

      <EmployeeForms />

      {error && <p className="text-sm text-rose-400">{error}</p>}

      <div className="grid gap-6 xl:grid-cols-2">
        <TaskList
          title="Assigned tasks"
          items={(tasks as Task[]).map((task) => ({
            id: task.id ?? '',
            title: task.title,
            description: task.description,
            status: task.status,
            meta: `Task ID: ${task.id}`,
          }))}
          emptyMessage="No assigned tasks yet."
        />

        <TaskList
          title="Recent analyses"
          items={(analyses as Analysis[]).map((analysis) => ({
            id: analysis.id ?? '',
            title: `Analysis ${analysis.id}`,
            description: `Task ${analysis.taskId}`,
            status: analysis.status,
            meta: `Readiness ${analysis.readinessScore}%`,
            href: `/employee/analysis/${analysis.id}`,
            ctaLabel: 'Continue Analysis',
          }))}
          emptyMessage="No analyses found."
        />
      </div>

      <GlassCard>
        <h2 className="text-sm font-semibold text-white">Filter analyses</h2>
        <form method="get" className="mt-4 grid gap-3 md:grid-cols-3">
          <select
            name="status"
            defaultValue={status}
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white"
          >
            <option value="">All Statuses</option>
            <option value="DRAFT">DRAFT</option>
            <option value="SUBMITTED">SUBMITTED</option>
            <option value="NEEDS_CHANGES">NEEDS_CHANGES</option>
            <option value="APPROVED">APPROVED</option>
          </select>
          <input
            name="limit"
            defaultValue={limit}
            placeholder="Limit (1-50)"
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
          />
          <button
            type="submit"
            className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400"
          >
            Apply filters
          </button>
        </form>
      </GlassCard>
    </div>
  );
}
