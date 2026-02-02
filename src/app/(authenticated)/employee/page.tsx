import React from 'react';
import { listAnalysesForEmployee, startAnalysisAction, suggestHypothesesAction, submitAnalysisAction } from '@/src/app/actions';

type EmployeePageProps = {
  searchParams?: { employeeUid?: string; status?: string; limit?: string };
};

export default async function EmployeePage({ searchParams }: EmployeePageProps) {
  const employeeUid = searchParams?.employeeUid ?? '';
  const status = searchParams?.status ?? '';
  const limit = searchParams?.limit ?? '';
  let analyses: Awaited<ReturnType<typeof listAnalysesForEmployee>> = [];
  let error: string | null = null;

  if (employeeUid) {
    try {
      const parsedLimit = Number.isNaN(Number(limit)) ? undefined : Number(limit);
      analyses = await listAnalysesForEmployee(employeeUid, {
        status: status ? (status as 'DRAFT' | 'SUBMITTED' | 'NEEDS_CHANGES' | 'APPROVED') : undefined,
        limit: parsedLimit,
      });
    } catch (err) {
      error = String(err);
    }
  }

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold">Employee Dashboard</h1>
        <p className="text-sm text-slate-600">Start analyses, request AI hypothesis suggestions, and submit when ready.</p>
      </header>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Start Analysis</h2>
        <form action={startAnalysisAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="taskId" placeholder="Task ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded">Start Analysis</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Suggest Hypotheses (AI)</h2>
        <form action={suggestHypothesesAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded">Suggest Hypotheses</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Submit Analysis</h2>
        <form action={submitAnalysisAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-emerald-600 text-white rounded">Submit</button>
        </form>
        <p className="mt-2 text-xs text-slate-500">Submissions require a readiness score ≥ 75.</p>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Your Recent Analyses</h2>
        <form method="get" className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <select name="status" defaultValue={status} className="w-full border p-2">
            <option value="">All Statuses</option>
            <option value="DRAFT">DRAFT</option>
            <option value="SUBMITTED">SUBMITTED</option>
            <option value="NEEDS_CHANGES">NEEDS_CHANGES</option>
            <option value="APPROVED">APPROVED</option>
          </select>
          <input name="limit" defaultValue={limit} placeholder="Limit (1-50)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-slate-800 text-white rounded">Refresh</button>
        </form>

        {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

        {analyses.length === 0 && !error && (
          <p className="mt-3 text-sm text-slate-600">No analyses found.</p>
        )}

        {analyses.length > 0 && (
          <div className="mt-3 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-slate-500">
                  <th className="py-2">Analysis ID</th>
                  <th className="py-2">Task</th>
                  <th className="py-2">Status</th>
                  <th className="py-2">Readiness</th>
                </tr>
              </thead>
              <tbody>
                {analyses.map((analysis) => (
                  <tr key={analysis.id} className="border-t">
                    <td className="py-2 text-slate-700">{analysis.id}</td>
                    <td className="py-2 text-slate-700">{analysis.taskId}</td>
                    <td className="py-2 text-slate-700">{analysis.status}</td>
                    <td className="py-2 text-slate-700">{analysis.readinessScore}</td>
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
