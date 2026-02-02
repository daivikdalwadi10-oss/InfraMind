"use client";

import { useFormState, useFormStatus } from "react-dom";
import {
  startAnalysisForm,
  suggestHypothesesForm,
  submitAnalysisForm,
  type ActionResult,
} from "@/src/app/actions";

type EmployeeFormsProps = {
  employeeUid?: string;
};

const initialState: ActionResult = { ok: true, message: "" };

function SubmitButton({ label }: { label: string }) {
  const { pending } = useFormStatus();
  return (
    <button type="submit" className="px-4 py-2 bg-slate-900 text-white rounded" disabled={pending}>
      {pending ? "Working..." : label}
    </button>
  );
}

function StatusMessage({ state }: { state: ActionResult }) {
  if (!state.message) return null;
  return (
    <p className={`mt-2 text-sm ${state.ok ? "text-emerald-600" : "text-red-600"}`}>
      {state.message}
    </p>
  );
}

export default function EmployeeForms({ employeeUid }: EmployeeFormsProps) {
  const [startState, startAction] = useFormState(startAnalysisForm, initialState);
  const [suggestState, suggestAction] = useFormState(suggestHypothesesForm, initialState);
  const [submitState, submitAction] = useFormState(submitAnalysisForm, initialState);

  return (
    <div className="space-y-6">
      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Start Analysis</h2>
        <form action={startAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="taskId" placeholder="Task ID" className="w-full border p-2" />
          <SubmitButton label="Start Analysis" />
          <StatusMessage state={startState} />
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Suggest Hypotheses (AI)</h2>
        <form action={suggestAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <SubmitButton label="Suggest Hypotheses" />
          <StatusMessage state={suggestState} />
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Submit Analysis</h2>
        <form action={submitAction} className="mt-2 grid gap-2 max-w-md">
          <input name="employeeUid" defaultValue={employeeUid} placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <SubmitButton label="Submit" />
          <StatusMessage state={submitState} />
        </form>
        <p className="mt-2 text-xs text-slate-500">Submissions require a readiness score ≥ 75.</p>
      </section>
    </div>
  );
}
