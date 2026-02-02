import React from 'react';
import { startAnalysisAction, suggestHypothesesAction, submitAnalysisAction } from '@/src/app/actions';

export default function EmployeePage() {
  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold">Employee Dashboard</h1>
        <p className="text-sm text-slate-600">Start analyses, request AI hypothesis suggestions, and submit when ready.</p>
      </header>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Start Analysis</h2>
        <form action={startAnalysisAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="taskId" placeholder="Task ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded">Start Analysis</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Suggest Hypotheses (AI)</h2>
        <form action={suggestHypothesesAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded">Suggest Hypotheses</button>
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Submit Analysis</h2>
        <form action={submitAnalysisAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-emerald-600 text-white rounded">Submit</button>
        </form>
        <p className="mt-2 text-xs text-slate-500">Submissions require a readiness score ≥ 75.</p>
      </section>
    </div>
  );
}
