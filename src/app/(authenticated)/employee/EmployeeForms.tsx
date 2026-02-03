"use client";

import React from "react";
import {
  startAnalysisForm,
  suggestHypothesesForm,
  submitAnalysisForm,
  type ActionResult,
} from "@/src/app/actions";
import GlassCard from "@/src/components/ui/GlassCard";
import { useToast } from "@/src/components/ui/Toast";
import { useRouter } from "next/navigation";

type EmployeeFormsProps = {};

const initialState: ActionResult = { ok: true, message: "" };

function PrimaryButton({ label, pending }: { label: string; pending: boolean }) {
  return (
    <button
      type="submit"
      className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-60"
      disabled={pending}
    >
      {pending ? "Working..." : label}
    </button>
  );
}

function Field({
  name,
  defaultValue,
  placeholder,
}: {
  name: string;
  defaultValue?: string;
  placeholder: string;
}) {
  return (
    <input
      name={name}
      defaultValue={defaultValue}
      placeholder={placeholder}
      className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none"
    />
  );
}

function ActionSection({
  title,
  description,
  onSubmit,
  children,
  pending,
  buttonLabel,
}: {
  title: string;
  description: string;
  onSubmit: (_event: React.FormEvent<HTMLFormElement>) => void;
  children: React.ReactNode;
  pending: boolean;
  buttonLabel: string;
}) {
  return (
    <div className="space-y-3">
      <div>
        <h3 className="text-sm font-semibold text-white">{title}</h3>
        <p className="mt-1 text-xs text-slate-400">{description}</p>
      </div>
      <form onSubmit={onSubmit} className="grid gap-3 md:grid-cols-2">
        {children}
        <div className="md:col-span-2">
          <PrimaryButton label={buttonLabel} pending={pending} />
        </div>
      </form>
    </div>
  );
}

export default function EmployeeForms({}: EmployeeFormsProps) {
  const { show } = useToast();
  const router = useRouter();
  const [starting, startTransition] = React.useTransition();
  const [suggesting, suggestTransition] = React.useTransition();
  const [submitting, submitTransition] = React.useTransition();

  const handleSubmit = (
    action: (_prev: ActionResult, _formData: FormData) => Promise<ActionResult>,
    title: string,
    transition: React.TransitionStartFunction
  ) =>
    (event: React.FormEvent<HTMLFormElement>) => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      transition(async () => {
        const res = await action(initialState, formData);
        show({
          title: res.ok ? title : "Action failed",
          description: res.message,
          variant: res.ok ? "success" : "error",
        });
        if (res.ok) router.refresh();
      });
    };

  return (
    <GlassCard className="space-y-8">
      <ActionSection
        title="Start analysis"
        description="Kick off a new analysis for your assigned task."
        onSubmit={handleSubmit(startAnalysisForm, "Analysis started", startTransition)}
        pending={starting}
        buttonLabel="Start Analysis"
      >
        <Field name="taskId" placeholder="Task ID" />
        <select
          name="analysisType"
          className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-blue-400 focus:outline-none"
        >
          <option value="LATENCY">LATENCY</option>
          <option value="SECURITY">SECURITY</option>
          <option value="OUTAGE">OUTAGE</option>
          <option value="CAPACITY">CAPACITY</option>
        </select>
      </ActionSection>

      <div className="border-t border-white/10" />

      <ActionSection
        title="Suggest hypotheses"
        description="Get AI-assisted hypotheses for a draft analysis."
        onSubmit={handleSubmit(suggestHypothesesForm, "Hypotheses suggested", suggestTransition)}
        pending={suggesting}
        buttonLabel="Suggest Hypotheses"
      >
        <Field name="analysisId" placeholder="Analysis ID" />
      </ActionSection>

      <div className="border-t border-white/10" />

      <ActionSection
        title="Submit analysis"
        description="Submit when readiness score meets the required threshold."
        onSubmit={handleSubmit(submitAnalysisForm, "Analysis submitted", submitTransition)}
        pending={submitting}
        buttonLabel="Submit"
      >
        <Field name="analysisId" placeholder="Analysis ID" />
        <p className="md:col-span-2 text-xs text-slate-500">Submissions require a readiness score ≥ 75.</p>
      </ActionSection>
    </GlassCard>
  );
}
