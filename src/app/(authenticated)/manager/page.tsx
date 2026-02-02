import React from 'react';
import { listReportsForManager, listTasksForManager } from '@/src/app/actions';
import ManagerForms from './ManagerForms';

type ManagerPageProps = {
  searchParams?: { managerUid?: string; taskStatus?: string; reportStatus?: string; limit?: string };
};

export default async function ManagerPage({ searchParams }: ManagerPageProps) {
  const managerUid = searchParams?.managerUid ?? '';
  const taskStatus = searchParams?.taskStatus ?? '';
  const reportStatus = searchParams?.reportStatus ?? '';
  const limit = searchParams?.limit ?? '';
  let tasks: Awaited<ReturnType<typeof listTasksForManager>> = [];
  let reports: Awaited<ReturnType<typeof listReportsForManager>> = [];
  let error: string | null = null;

  if (managerUid) {
    try {
      const parsedLimit = Number.isNaN(Number(limit)) ? undefined : Number(limit);
      tasks = await listTasksForManager(managerUid, {
        status: taskStatus ? (taskStatus as 'OPEN' | 'ASSIGNED' | 'CLOSED') : undefined,
        limit: parsedLimit,
      });
      reports = await listReportsForManager(managerUid, {
        status: reportStatus ? (reportStatus as 'DRAFT' | 'FINALIZED') : undefined,
        limit: parsedLimit,
      });
    } catch (err) {
      error = String(err);
    }
  }

  // Simple server form using Server Action createTask
  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold">Manager Dashboard</h1>
        <p className="text-sm text-slate-600">Create tasks, review submitted analyses, and finalize reports.</p>
      </header>

      <ManagerForms managerUid={managerUid} />

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Your Recent Tasks</h2>
        <form method="get" className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <select name="taskStatus" defaultValue={taskStatus} className="w-full border p-2">
            <option value="">All Statuses</option>
            <option value="OPEN">OPEN</option>
            <option value="ASSIGNED">ASSIGNED</option>
            <option value="CLOSED">CLOSED</option>
          </select>
          <input name="limit" defaultValue={limit} placeholder="Limit (1-50)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-slate-800 text-white rounded">Refresh</button>
        </form>

        {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

        {tasks.length === 0 && !error && (
          <p className="mt-3 text-sm text-slate-600">No tasks found.</p>
        )}

        {tasks.length > 0 && (
          <div className="mt-3 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-slate-500">
                  <th className="py-2">Task ID</th>
                  <th className="py-2">Title</th>
                  <th className="py-2">Assignee</th>
                  <th className="py-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {tasks.map((task) => (
                  <tr key={task.id} className="border-t">
                    <td className="py-2 text-slate-700">{task.id}</td>
                    <td className="py-2 text-slate-700">{task.title}</td>
                    <td className="py-2 text-slate-700">{task.assignee ?? 'Unassigned'}</td>
                    <td className="py-2 text-slate-700">{task.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Your Recent Reports</h2>
        <form method="get" className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <select name="reportStatus" defaultValue={reportStatus} className="w-full border p-2">
            <option value="">All Statuses</option>
            <option value="DRAFT">DRAFT</option>
            <option value="FINALIZED">FINALIZED</option>
          </select>
          <input name="limit" defaultValue={limit} placeholder="Limit (1-50)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-slate-800 text-white rounded">Refresh</button>
        </form>
        {reports.length === 0 && !error && (
          <p className="mt-3 text-sm text-slate-600">No reports found.</p>
        )}

        {reports.length > 0 && (
          <div className="mt-3 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-slate-500">
                  <th className="py-2">Report ID</th>
                  <th className="py-2">Task</th>
                  <th className="py-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {reports.map((report) => (
                  <tr key={report.id} className="border-t">
                    <td className="py-2 text-slate-700">{report.id}</td>
                    <td className="py-2 text-slate-700">{report.taskId}</td>
                    <td className="py-2 text-slate-700">{report.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
