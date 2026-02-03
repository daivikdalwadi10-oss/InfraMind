"use client";

import React from "react";
import * as Dialog from "@radix-ui/react-dialog";
import { X } from "lucide-react";
import {
  createTaskForm,
  managerReviewForm,
  type ActionResult,
} from "@/src/app/actions";
import GlassCard from "@/src/components/ui/GlassCard";
import { useToast } from "@/src/components/ui/Toast";
import { useRouter } from "next/navigation";

type ManagerFormsProps = {};

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

export default function ManagerForms({}: ManagerFormsProps) {
  const { show } = useToast();
  const router = useRouter();
  const [creating, createTransition] = React.useTransition();
  const [reviewing, reviewTransition] = React.useTransition();

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
      <div className="space-y-3">
        <div>
          <h2 className="text-sm font-semibold text-white">Task creation</h2>
          <p className="mt-1 text-xs text-slate-400">Assign work and track status transitions.</p>
        </div>
        <Dialog.Root>
          <Dialog.Trigger className="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/20">
            Create Task
          </Dialog.Trigger>
          <Dialog.Portal>
            <Dialog.Overlay className="fixed inset-0 bg-black/60" />
            <Dialog.Content className="fixed left-1/2 top-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/10 bg-slate-950/95 p-6 shadow-xl shadow-black/30 backdrop-blur">
              <div className="flex items-start justify-between">
                <Dialog.Title className="text-lg font-semibold text-white">New task</Dialog.Title>
                <Dialog.Close className="rounded-full p-1 text-slate-400 hover:text-white">
                  <X className="h-4 w-4" />
                </Dialog.Close>
              </div>
              <p className="mt-2 text-xs text-slate-400">Provide clear context before assigning an analyst.</p>
              <form onSubmit={handleSubmit(createTaskForm, "Task created", createTransition)} className="mt-4 grid gap-3">
                <Field name="title" placeholder="Title" />
                <textarea
                  name="description"
                  placeholder="Description"
                  className="min-h-[100px] w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none"
                />
                <Field name="assignedTo" placeholder="Assign to (employee UID)" />
                <div className="flex justify-end">
                  <PrimaryButton label="Create Task" pending={creating} />
                </div>
              </form>
            </Dialog.Content>
          </Dialog.Portal>
        </Dialog.Root>
      </div>

      <div className="border-t border-white/10" />

      <div className="space-y-3">
        <div>
          <h2 className="text-sm font-semibold text-white">Review analysis</h2>
          <p className="mt-1 text-xs text-slate-400">Approve or request changes for submitted analyses.</p>
        </div>
        <form onSubmit={handleSubmit(managerReviewForm, "Review submitted", reviewTransition)} className="grid gap-3 md:grid-cols-2">
          <Field name="analysisId" placeholder="Analysis ID" />
          <select
            name="type"
            className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-blue-400 focus:outline-none"
          >
            <option value="APPROVE">APPROVE</option>
            <option value="NEEDS_CHANGES">NEEDS_CHANGES</option>
          </select>
          <textarea
            name="feedback"
            placeholder="Feedback (required for needs changes)"
            className="min-h-[80px] w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-blue-400 focus:outline-none md:col-span-2"
          />
          <div className="md:col-span-2">
            <PrimaryButton label="Submit Review" pending={reviewing} />
          </div>
        </form>
      </div>

    </GlassCard>
  );
}
