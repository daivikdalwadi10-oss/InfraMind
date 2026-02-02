import React from 'react';
import { createTaskAction, finalizeReportAction, listReportsForManager, listTasksForManager, managerReviewAction } from '@/src/app/actions';

type ManagerPageProps = {
  searchParams?: { managerUid?: string };
};

export default async function ManagerPage({ searchParams }: ManagerPageProps) {
  const managerUid = searchParams?.managerUid ?? '';
  let tasks: Awaited<ReturnType<typeof listTasksForManager>> = [];
  let reports: Awaited<ReturnType<typeof listReportsForManager>> = [];
  let error: string | null = null;

  if (managerUid) {
    try {
      tasks = await listTasksForManager(managerUid);
      reports = await listReportsForManager(managerUid);
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

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Create Task</h2>
        <form action={createTaskAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="title" placeholder="Title" className="w-full border p-2" />
          <textarea name="description" placeholder="Description" className="w-full border p-2" />
          <input name="assignee" placeholder="Assignee UID (optional)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Review Analysis</h2>
        <form action={managerReviewAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <select name="type" className="w-full border p-2">
            <option value="APPROVE">APPROVE</option>
            <option value="NEEDS_CHANGES">NEEDS_CHANGES</option>
          </select>
          <textarea name="note" placeholder="Optional note" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded">Submit Review</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Finalize Report</h2>
        <form action={finalizeReportAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="reportId" placeholder="Report ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-emerald-600 text-white rounded">Finalize</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Your Recent Tasks</h2>
        <form method="get" className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
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
