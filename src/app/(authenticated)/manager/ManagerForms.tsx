"use client";

import { useFormState, useFormStatus } from "react-dom";
import {
  createTaskForm,
  finalizeReportForm,
  managerReviewForm,
  type ActionResult,
} from "@/src/app/actions";

type ManagerFormsProps = {
  managerUid?: string;
};

const initialState: ActionResult = { ok: true, message: "" };

function SubmitButton({ label, tone }: { label: string; tone?: "primary" | "secondary" | "success" }) {
  const { pending } = useFormStatus();
  const className =
    tone === "secondary"
      ? "px-4 py-2 bg-indigo-600 text-white rounded"
      : tone === "success"
        ? "px-4 py-2 bg-emerald-600 text-white rounded"
        : "px-4 py-2 bg-blue-600 text-white rounded";
  return (
    <button type="submit" className={className} disabled={pending}>
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

export default function ManagerForms({ managerUid }: ManagerFormsProps) {
  const [createState, createAction] = useFormState(createTaskForm, initialState);
  const [reviewState, reviewAction] = useFormState(managerReviewForm, initialState);
  const [finalizeState, finalizeAction] = useFormState(finalizeReportForm, initialState);

  return (
    <div className="space-y-6">
      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Create Task</h2>
        <form action={createAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="title" placeholder="Title" className="w-full border p-2" />
          <textarea name="description" placeholder="Description" className="w-full border p-2" />
          <input name="assignee" placeholder="Assignee UID (optional)" className="w-full border p-2" />
          <SubmitButton label="Create" />
          <StatusMessage state={createState} />
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Review Analysis</h2>
        <form action={reviewAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="analysisId" placeholder="Analysis ID" className="w-full border p-2" />
          <select name="type" className="w-full border p-2">
            <option value="APPROVE">APPROVE</option>
            <option value="NEEDS_CHANGES">NEEDS_CHANGES</option>
          </select>
          <textarea name="note" placeholder="Optional note" className="w-full border p-2" />
          <SubmitButton label="Submit Review" tone="secondary" />
          <StatusMessage state={reviewState} />
        </form>
      </section>

      <section className="rounded-lg border bg-white p-4">
        <h2 className="font-medium">Finalize Report</h2>
        <form action={finalizeAction} className="mt-2 grid gap-2 max-w-md">
          <input name="managerUid" defaultValue={managerUid} placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="reportId" placeholder="Report ID" className="w-full border p-2" />
          <SubmitButton label="Finalize" tone="success" />
          <StatusMessage state={finalizeState} />
        </form>
      </section>
    </div>
  );
}
