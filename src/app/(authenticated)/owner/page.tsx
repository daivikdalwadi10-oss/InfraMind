import React from 'react';
import { getFinalizedReportForOwner, listFinalizedReportsForOwner } from '@/src/app/actions';

type OwnerPageProps = {
  searchParams?: { ownerUid?: string; reportId?: string };
};

export default async function OwnerPage({ searchParams }: OwnerPageProps) {
  const ownerUid = searchParams?.ownerUid ?? '';
  const reportId = searchParams?.reportId ?? '';
  let report: Awaited<ReturnType<typeof getFinalizedReportForOwner>> | null = null;
  let reports: Awaited<ReturnType<typeof listFinalizedReportsForOwner>> = [];
  let error: string | null = null;

  if (ownerUid && reportId) {
    try {
      report = await getFinalizedReportForOwner(ownerUid, reportId);
    } catch (err) {
      error = String(err);
    }
  }

  if (ownerUid) {
    try {
      reports = await listFinalizedReportsForOwner(ownerUid);
    } catch (err) {
      error = String(err);
    }
  }

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold">Owner Dashboard</h1>
        <p className="text-sm text-slate-600">Owners can view finalized reports only.</p>
      </header>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Find Finalized Report</h2>
        <form method="get" className="mt-2 grid gap-2 max-w-md">
          <input name="ownerUid" defaultValue={ownerUid} placeholder="Your Owner UID" className="w-full border p-2" />
          <input name="reportId" defaultValue={reportId} placeholder="Report ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-slate-800 text-white rounded">View Report</button>
        </form>

        {error && (
          <p className="mt-3 text-sm text-red-600">{error}</p>
        )}

        {report && (
          <div className="mt-4 rounded border bg-slate-50 p-4">
            <div className="text-sm text-slate-600">Report ID: {report.id}</div>
            <div className="text-sm text-slate-600">Task ID: {report.taskId}</div>
            <div className="text-sm text-slate-600">Status: {report.status}</div>
            <div className="mt-3 text-sm font-medium">Executive Summary</div>
            <p className="mt-1 text-sm text-slate-700">
              {report.executiveSummaryDraft?.text ?? 'No summary available.'}
            </p>
          </div>
        )}
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Finalized Reports (Recent)</h2>
        {reports.length === 0 && !error && (
          <p className="mt-3 text-sm text-slate-600">No finalized reports found.</p>
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
                {reports.map((item) => (
                  <tr key={item.id} className="border-t">
                    <td className="py-2 text-slate-700">{item.id}</td>
                    <td className="py-2 text-slate-700">{item.taskId}</td>
                    <td className="py-2 text-slate-700">{item.status}</td>
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
