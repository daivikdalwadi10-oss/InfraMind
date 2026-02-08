"use client";

import Link from 'next/link';
import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  AlertTriangle,
  Brain,
  CheckCircle2,
  ClipboardList,
  Cpu,
  Database,
  GitBranch,
  Lock,
  MessageSquare,
  Monitor,
  ShieldCheck,
  Sparkles,
  Target,
  Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useSession } from '@/hooks/useSession';

const purposeItems = [
  {
    icon: Target,
    title: 'Why InfraMind exists',
    description:
      'Enterprise incident response needs a shared source of truth. InfraMind ties engineering reality to executive decisions.',
  },
  {
    icon: AlertTriangle,
    title: 'The cost of unstructured analysis',
    description:
      'Free-form writeups blur evidence, timelines, and risk. That slows remediation and creates repeat incidents.',
  },
  {
    icon: ShieldCheck,
    title: 'Workflow-enforced accountability',
    description:
      'Structured inputs, readiness scoring, and audit trails keep decisions consistent and defensible.',
  },
];

const differentiators = [
  {
    icon: Monitor,
    title: 'Not a monitoring tool',
    description: 'InfraMind assumes signals already exist. It governs how teams interpret them.',
  },
  {
    icon: ClipboardList,
    title: 'Not a ticketing system',
    description: 'It is built for analytical rigor, not queue management or service desks.',
  },
  {
    icon: MessageSquare,
    title: 'Not a chatbot',
    description: 'AI assists inside the workflow, but never replaces human judgment.',
  },
  {
    icon: Brain,
    title: 'A systems-engineering decision platform',
    description: 'Bring evidence, risk, and accountability into a single executive-ready narrative.',
  },
];

const inputItems = [
  { icon: CheckCircle2, label: 'Symptoms' },
  { icon: Sparkles, label: 'Signals' },
  { icon: Target, label: 'Environment context' },
  { icon: GitBranch, label: 'Timeline of events' },
  { icon: Users, label: 'Dependency impact' },
  { icon: AlertTriangle, label: 'Risk classification' },
];

const responsibilityItems = [
  'AI assists, humans decide every action.',
  'AI never auto-approves or publishes.',
  'All AI outputs are editable before submission.',
  'Every AI action is logged and auditable.',
];

const roleItems = [
  {
    icon: Users,
    title: 'Employee',
    description: 'Captures evidence, drafts hypotheses, and validates readiness before submission.',
  },
  {
    icon: ShieldCheck,
    title: 'Manager',
    description: 'Assigns teams, reviews analysis, and finalizes reports with full accountability.',
  },
  {
    icon: Lock,
    title: 'Owner',
    description: 'Consumes finalized reports and oversees enterprise risk posture.',
  },
];

const workflowSteps = ['Task', 'Analysis', 'Review', 'Approval', 'Report'];

const architectureItems = [
  { icon: Monitor, title: 'Frontend', description: 'Next.js app router with role-aware navigation.' },
  { icon: Cpu, title: 'Backend', description: 'PHP services with auditable workflows and validation.' },
  { icon: Database, title: 'Database', description: 'Structured evidence, inputs, outputs, and history.' },
  { icon: Lock, title: 'IAM', description: 'Role-based access control and ownership boundaries.' },
  { icon: Sparkles, title: 'AI orchestration', description: 'Genkit flows for hypotheses and reporting.' },
];

export default function HomePage() {
  const router = useRouter();
  const { status } = useSession();

  useEffect(() => {
    if (status === 'authenticated') {
      router.replace('/dashboard');
    }
  }, [status, router]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-amber-50 text-ink">
      <div className="relative overflow-hidden">
        <div className="absolute -top-32 right-0 h-96 w-96 rounded-full bg-amber-200/40 blur-3xl" />
        <div className="absolute -bottom-24 left-0 h-96 w-96 rounded-full bg-slate-200/60 blur-3xl" />
        <div className="absolute left-1/3 top-20 h-72 w-72 rounded-full bg-white/70 blur-3xl" />

        <div className="relative mx-auto max-w-6xl px-6 py-16">
          <header className="flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
            <div className="max-w-2xl">
              <p className="text-xs uppercase tracking-[0.3em] text-muted">InfraMind</p>
              <h1
                className="mt-3 text-4xl font-semibold text-ink md:text-5xl"
                style={{ fontFamily: 'Space Grotesk, ui-sans-serif, system-ui' }}
              >
                Systems engineering intelligence for high-stakes incident response.
              </h1>
              <p className="mt-4 text-base text-muted">
                InfraMind turns fragmented incident analysis into a structured, auditable decision workflow for
                technical leaders and executive stakeholders.
              </p>
              <div className="mt-6 flex flex-wrap gap-3">
                <Button asChild className="rounded-full">
                  <Link href="/login">Get Started</Link>
                </Button>
                <Button asChild variant="secondary" className="rounded-full">
                  <Link href="/login">Login</Link>
                </Button>
              </div>
              <div className="mt-6 flex flex-wrap gap-4 text-xs text-muted">
                <div className="flex items-center gap-2 rounded-full border border-white/60 bg-white/60 px-3 py-1 backdrop-blur">
                  <ShieldCheck className="h-3.5 w-3.5" />
                  Workflow enforced
                </div>
                <div className="flex items-center gap-2 rounded-full border border-white/60 bg-white/60 px-3 py-1 backdrop-blur">
                  <Sparkles className="h-3.5 w-3.5" />
                  AI guided, never auto-approved
                </div>
                <div className="flex items-center gap-2 rounded-full border border-white/60 bg-white/60 px-3 py-1 backdrop-blur">
                  <Lock className="h-3.5 w-3.5" />
                  Audit ready
                </div>
              </div>
            </div>

            <Card className="w-full max-w-md border-white/50 bg-white/60 p-6 shadow-glass backdrop-blur transition hover:-translate-y-1 hover:shadow-xl">
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <div className="rounded-xl bg-white/70 p-2">
                    <Brain className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-ink">Decision-grade analysis</p>
                    <p className="text-xs text-muted">Evidence, context, and risk captured in one flow.</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="rounded-xl bg-white/70 p-2">
                    <Users className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-ink">Role-based ownership</p>
                    <p className="text-xs text-muted">Employees, managers, and owners see what matters.</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="rounded-xl bg-white/70 p-2">
                    <Database className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-ink">Structured record</p>
                    <p className="text-xs text-muted">Every input and outcome is stored for auditability.</p>
                  </div>
                </div>
              </div>
            </Card>
          </header>

          <section className="mt-16">
            <div className="flex items-center gap-3">
              <div className="rounded-full bg-white/70 p-2">
                <Target className="h-4 w-4" />
              </div>
              <h2 className="text-2xl font-semibold text-ink">InfraMind purpose</h2>
            </div>
            <div className="mt-6 grid gap-6 md:grid-cols-3">
              {purposeItems.map((item) => (
                <Card
                  key={item.title}
                  className="border-white/60 bg-white/60 p-6 shadow-glass backdrop-blur transition hover:-translate-y-1 hover:shadow-xl"
                >
                  <item.icon className="h-5 w-5 text-ink" />
                  <h3 className="mt-4 text-base font-semibold text-ink">{item.title}</h3>
                  <p className="mt-2 text-sm text-muted">{item.description}</p>
                </Card>
              ))}
            </div>
          </section>

          <section className="mt-16">
            <div className="flex items-center gap-3">
              <div className="rounded-full bg-white/70 p-2">
                <ShieldCheck className="h-4 w-4" />
              </div>
              <h2 className="text-2xl font-semibold text-ink">What makes InfraMind different</h2>
            </div>
            <div className="mt-6 grid gap-6 md:grid-cols-2">
              {differentiators.map((item) => (
                <Card
                  key={item.title}
                  className="border-white/60 bg-white/60 p-6 shadow-glass backdrop-blur transition hover:-translate-y-1 hover:shadow-xl"
                >
                  <div className="flex items-center gap-3">
                    <div className="rounded-xl bg-white/70 p-2">
                      <item.icon className="h-4 w-4" />
                    </div>
                    <h3 className="text-base font-semibold text-ink">{item.title}</h3>
                  </div>
                  <p className="mt-3 text-sm text-muted">{item.description}</p>
                </Card>
              ))}
            </div>
          </section>

          <section className="mt-16">
            <div className="flex items-center gap-3">
              <div className="rounded-full bg-white/70 p-2">
                <ClipboardList className="h-4 w-4" />
              </div>
              <h2 className="text-2xl font-semibold text-ink">Systems engineering inputs</h2>
            </div>
            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {inputItems.map((item) => (
                <div
                  key={item.label}
                  className="flex items-center gap-3 rounded-2xl border border-white/60 bg-white/60 px-4 py-4 shadow-glass backdrop-blur transition hover:-translate-y-1 hover:shadow-xl"
                >
                  <div className="rounded-xl bg-white/70 p-2">
                    <item.icon className="h-4 w-4" />
                  </div>
                  <span className="text-sm font-medium text-ink">{item.label}</span>
                </div>
              ))}
            </div>
          </section>

          <section className="mt-16 grid gap-6 lg:grid-cols-2">
            <Card className="border-white/60 bg-white/60 p-6 shadow-glass backdrop-blur">
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-white/70 p-2">
                  <Sparkles className="h-4 w-4" />
                </div>
                <h2 className="text-xl font-semibold text-ink">Human + AI responsibility model</h2>
              </div>
              <ul className="mt-4 space-y-3 text-sm text-muted">
                {responsibilityItems.map((item) => (
                  <li key={item} className="flex items-start gap-2">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 text-ink" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
            </Card>

            <Card className="border-white/60 bg-white/60 p-6 shadow-glass backdrop-blur">
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-white/70 p-2">
                  <Users className="h-4 w-4" />
                </div>
                <h2 className="text-xl font-semibold text-ink">Role-based accountability</h2>
              </div>
              <div className="mt-4 grid gap-4">
                {roleItems.map((item) => (
                  <div key={item.title} className="rounded-2xl border border-white/60 bg-white/70 p-4">
                    <div className="flex items-center gap-3">
                      <item.icon className="h-4 w-4" />
                      <p className="text-sm font-semibold text-ink">{item.title}</p>
                    </div>
                    <p className="mt-2 text-sm text-muted">{item.description}</p>
                  </div>
                ))}
              </div>
            </Card>
          </section>

          <section className="mt-16">
            <div className="flex items-center gap-3">
              <div className="rounded-full bg-white/70 p-2">
                <Cpu className="h-4 w-4" />
              </div>
              <h2 className="text-2xl font-semibold text-ink">End-to-end workflow</h2>
            </div>
            <div className="mt-6 rounded-3xl border border-white/60 bg-white/60 px-6 py-8 shadow-glass backdrop-blur">
              <div className="flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between">
                {workflowSteps.map((step, index) => (
                  <div key={step} className="flex items-center gap-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full border border-white/70 bg-white/80 text-sm font-semibold text-ink">
                      {index + 1}
                    </div>
                    <span className="text-sm font-semibold text-ink">{step}</span>
                  </div>
                ))}
              </div>
              <p className="mt-6 text-sm text-muted">
                Each phase is gated by readiness checks, role permissions, and an immutable audit trail.
              </p>
            </div>
          </section>

          <section className="mt-16">
            <div className="flex items-center gap-3">
              <div className="rounded-full bg-white/70 p-2">
                <Database className="h-4 w-4" />
              </div>
              <h2 className="text-2xl font-semibold text-ink">Architecture snapshot</h2>
            </div>
            <div className="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {architectureItems.map((item) => (
                <Card
                  key={item.title}
                  className="border-white/60 bg-white/60 p-6 shadow-glass backdrop-blur transition hover:-translate-y-1 hover:shadow-xl"
                >
                  <div className="flex items-center gap-3">
                    <div className="rounded-xl bg-white/70 p-2">
                      <item.icon className="h-4 w-4" />
                    </div>
                    <h3 className="text-base font-semibold text-ink">{item.title}</h3>
                  </div>
                  <p className="mt-3 text-sm text-muted">{item.description}</p>
                </Card>
              ))}
            </div>
          </section>

          <section className="mt-16 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-ink px-6 py-8 text-white">
            <div>
              <h2 className="text-xl font-semibold" style={{ fontFamily: 'Space Grotesk, ui-sans-serif, system-ui' }}>
                Ready to orchestrate your next incident response?
              </h2>
              <p className="mt-2 text-sm text-white/80">
                Launch the workspace and start capturing real signals with accountability built in.
              </p>
            </div>
            <div className="flex gap-3">
              <Button asChild className="rounded-full bg-white text-ink hover:bg-white/90">
                <Link href="/login">Get Started</Link>
              </Button>
              <Button asChild variant="secondary" className="rounded-full border-white/40 text-white hover:bg-white/10">
                <Link href="/login">Login</Link>
              </Button>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
