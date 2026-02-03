import React from 'react';
import { listReportsForManager, listSubmittedAnalysesForManager, listTasksForManager } from '@/src/app/actions';
import ManagerForms from './ManagerForms';
import PageHeader from '@/src/components/ui/PageHeader';
import TaskList from '@/src/components/ui/TaskList';
import GlassCard from '@/src/components/ui/GlassCard';
import type { Task, TimestampLike, Analysis } from '@/src/lib/types';

type ReportData = {
  id?: string;
  analysisId?: string;
  createdAt?: { seconds: number; nanoseconds?: number };
};

type ManagerSearchParams = { taskStatus?: string; limit?: string };
type ManagerPageProps = {
  searchParams?: ManagerSearchParams | Promise<ManagerSearchParams>;
};

export default async function ManagerPage({ searchParams }: ManagerPageProps) {
  const formatTimestamp = (value?: TimestampLike) =>
    value ? new Date(value.seconds * 1000).toLocaleDateString() : '—';
  const resolved = await (searchParams ?? {});
  const taskStatus = resolved.taskStatus ?? '';
  const limit = resolved.limit ?? '';
  let tasks: Awaited<ReturnType<typeof listTasksForManager>> = [];
  let reports: Awaited<ReturnType<typeof listReportsForManager>> = [];
  let reviewQueue: Awaited<ReturnType<typeof listSubmittedAnalysesForManager>> = [];
  let error: string | null = null;

  try {
    const parsedLimit = Number.isNaN(Number(limit)) ? undefined : Number(limit);
    tasks = await listTasksForManager({
      status: taskStatus ? (taskStatus as Task['status']) : undefined,
      limit: parsedLimit,
    });
    reports = await listReportsForManager({ limit: parsedLimit });
    reviewQueue = await listSubmittedAnalysesForManager({ limit: parsedLimit });
  } catch (err) {
    error = String(err);
  }

  // Simple server form using Server Action createTask
  return (
    <div className="space-y-8">
      <PageHeader title="Overview" subtitle="Create tasks, review analyses, and generate reports." />

      <ManagerForms />

      {error && <p className="text-sm text-rose-400">{error}</p>}

      <div className="grid gap-6 xl:grid-cols-2">
        <TaskList
          title="Review queue"
          items={(reviewQueue as Analysis[]).map((analysis) => ({
            id: analysis.id ?? '',
            title: `Analysis ${analysis.id}`,
            description: `Task ${analysis.taskId}`,
            status: analysis.status,
            meta: `Updated ${formatTimestamp(analysis.updatedAt)}`,
          }))}
          emptyMessage="No submitted analyses awaiting review."
        />

        <TaskList
          title="Recent tasks"
          items={(tasks as Task[]).map((task) => ({
            id: task.id ?? '',
            title: task.title,
            description: task.description,
            status: task.status,
            meta: `Assigned to ${task.assignedTo ?? 'Unassigned'}`,
          }))}
          emptyMessage="No tasks found."
        />
      </div>

      <TaskList
        title="Recent reports"
        items={(reports as ReportData[]).map((report) => ({
          id: report.id ?? '',
          title: `Report ${report.id}`,
          description: `Analysis ${report.analysisId}`,
          status: 'APPROVED',
          meta: `Created ${formatTimestamp(report.createdAt ? { seconds: report.createdAt.seconds, nanoseconds: report.createdAt.nanoseconds ?? 0 } : undefined)}`,
        }))}
        emptyMessage="No reports found."
      />

      <GlassCard>
        <h2 className="text-sm font-semibold text-white">Filter records</h2>
        <form method="get" className="mt-4 grid gap-3 md:grid-cols-3">
          <select
            name="taskStatus"
            defaultValue={taskStatus}
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white"
          >
            <option value="">All Task Statuses</option>
            <option value="OPEN">OPEN</option>
            <option value="IN_PROGRESS">IN_PROGRESS</option>
            <option value="COMPLETED">COMPLETED</option>
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
