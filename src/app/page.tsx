import Link from 'next/link';

export default function HomePage() {
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <main className="mx-auto flex max-w-5xl flex-col gap-8 px-6 py-12">
        <header className="space-y-4">
          <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">
            AI-assisted operational intelligence
          </div>
          <h1 className="text-4xl font-semibold text-white">InfraMind</h1>
          <p className="max-w-2xl text-base text-slate-300">
            Start from the role-based dashboard that matches your workflow. Sign in to access your workspace, or explore
            the role experiences below.
          </p>
          <div className="flex flex-wrap gap-3">
            <Link
              href="/login"
              className="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white hover:bg-white/10"
            >
              Sign in
            </Link>
            <Link
              href="/signup"
              className="rounded-xl bg-blue-500 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-400"
            >
              Create account
            </Link>
          </div>
        </header>

        <section className="grid gap-4 sm:grid-cols-3">
          <Link
            href="/employee"
            className="group rounded-2xl border border-white/10 bg-white/5 p-5 shadow-xl shadow-black/20 backdrop-blur transition hover:bg-white/10"
          >
            <div className="text-sm font-semibold text-white">Employee</div>
            <div className="mt-2 text-sm text-slate-300">Start analyses, capture signals, and submit for review.</div>
            <div className="mt-4 text-xs font-medium text-slate-400">Go to dashboard →</div>
          </Link>
          <Link
            href="/manager"
            className="group rounded-2xl border border-white/10 bg-white/5 p-5 shadow-xl shadow-black/20 backdrop-blur transition hover:bg-white/10"
          >
            <div className="text-sm font-semibold text-white">Manager</div>
            <div className="mt-2 text-sm text-slate-300">Create tasks, review analyses, and finalize reports.</div>
            <div className="mt-4 text-xs font-medium text-slate-400">Go to dashboard →</div>
          </Link>
          <Link
            href="/owner"
            className="group rounded-2xl border border-white/10 bg-white/5 p-5 shadow-xl shadow-black/20 backdrop-blur transition hover:bg-white/10"
          >
            <div className="text-sm font-semibold text-white">Owner</div>
            <div className="mt-2 text-sm text-slate-300">Review generated reports and executive summaries.</div>
            <div className="mt-4 text-xs font-medium text-slate-400">Go to dashboard →</div>
          </Link>
        </section>
      </main>
    </div>
  );
}
