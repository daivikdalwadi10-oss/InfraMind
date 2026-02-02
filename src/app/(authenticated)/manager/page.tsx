import React from 'react';
import { createTaskAction, finalizeReportAction, managerReviewAction } from '@/src/app/actions';

export default function ManagerPage() {
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
          <input name="managerUid" placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="title" placeholder="Title" className="w-full border p-2" />
          <textarea name="description" placeholder="Description" className="w-full border p-2" />
          <input name="assignee" placeholder="Assignee UID (optional)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Review Analysis</h2>
        <form action={managerReviewAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" placeholder="Your Manager UID" className="w-full border p-2" />
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
          <input name="managerUid" placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="reportId" placeholder="Report ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-emerald-600 text-white rounded">Finalize</button>
        </form>
      </section>
    </div>
  );
}
