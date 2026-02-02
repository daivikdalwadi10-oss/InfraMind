import admin from 'firebase-admin';
import { adminFirestore } from '@/src/firebase/admin';
import { assertHasRole } from '@/src/lib/auth';
import { Task, Analysis, Report, StatusHistoryEntry } from '@/src/lib/types';
import { suggestHypotheses } from '@/src/ai/flows/suggestHypotheses';
import { draftExecutiveSummary } from '@/src/ai/flows/draftExecutiveSummary';

// Helper types for Form-like inputs
type FormLike = FormData | Record<string, unknown>;
const getValue = (form: FormLike, k: string) => (form instanceof FormData ? form.get(k) : (form as Record<string, unknown>)[k]);

// Server-side actions for workflow enforcement. All functions MUST be called server-side and re-validate permissions.

export async function createTask(managerUid: string, payload: { title: string; description: string; assignee?: string }) {
  await assertHasRole(managerUid, 'manager');

  const now = Date.now();
  const task: Task = {
    title: payload.title,
    description: payload.description,
    creator: managerUid,
    assignee: payload.assignee,
    status: payload.assignee ? 'ASSIGNED' : 'OPEN',
    createdAt: now,
    updatedAt: now,
    statusHistory: [
      { status: payload.assignee ? 'ASSIGNED' : 'OPEN', changedAt: now, changedBy: managerUid } as StatusHistoryEntry,
    ],
  };

  const ref = await adminFirestore.collection('tasks').add(task);
  return { id: ref.id, ...task };
}

// Form-compatible wrappers for Server Action forms. These wrappers accept FormData (or plain params) and are intended to be used directly from forms
export async function createTaskAction(form: FormData | { title?: string; description?: string; assignee?: string; managerUid?: string }) {
  // Support both FormData (from <form action={createTaskAction}>) and plain object (tests)
  const get = (k: string) => getValue(form as FormLike, k);
  const managerUid = String(get('managerUid') ?? get('manager') ?? '');
  if (!managerUid) throw new Error('managerUid required in form');
  const payload = { title: String(get('title') ?? ''), description: String(get('description') ?? ''), assignee: String(get('assignee') ?? undefined) };
  await createTask(managerUid, payload);
  return;
}
export async function startAnalysis(employeeUid: string, taskId: string) {
  await assertHasRole(employeeUid, 'employee');

  // Verify task assignment
  const taskSnap = await adminFirestore.collection('tasks').doc(taskId).get();
  if (!taskSnap.exists) throw new Error('Task not found');
  const task = taskSnap.data() as Task;
  if (task.assignee && task.assignee !== employeeUid) throw new Error('Task not assigned to this user');

  const now = Date.now();
  const analysis: Analysis = {
    taskId,
    author: employeeUid,
    symptoms: [],
    signals: [],
    hypotheses: [],
    readinessScore: 0,
    status: 'DRAFT',
    createdAt: now,
    updatedAt: now,
    statusHistory: [{ status: 'DRAFT', changedAt: now, changedBy: employeeUid }],
  };

  const ref = await adminFirestore.collection('analyses').add(analysis);
  return { id: ref.id, ...analysis };
}

export async function startAnalysisAction(form: FormData | { taskId?: string; employeeUid?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const employeeUid = String(get('employeeUid') ?? '');
  const taskId = String(get('taskId') ?? '');
  if (!employeeUid || !taskId) throw new Error('employeeUid and taskId required');
  await startAnalysis(employeeUid, taskId);
  return;
}
export async function suggestHypothesesForAnalysis(employeeUid: string, analysisId: string) {
  await assertHasRole(employeeUid, 'employee');

  const snap = await adminFirestore.collection('analyses').doc(analysisId).get();
  if (!snap.exists) throw new Error('Analysis not found');
  const analysis = snap.data() as Analysis;
  if (analysis.author !== employeeUid) throw new Error('Not the author');
  if (analysis.status !== 'DRAFT') throw new Error('Can only suggest hypotheses while DRAFT');

  const hypotheses = await suggestHypotheses({ taskTitle: analysis.taskId, symptoms: analysis.symptoms, signals: analysis.signals });

  // merge selected hypotheses
  analysis.hypotheses = hypotheses.map((h) => ({ text: h.text, confidence: h.confidence }));
  analysis.updatedAt = Date.now();

  await adminFirestore.collection('analyses').doc(analysisId).update({ hypotheses: analysis.hypotheses, updatedAt: analysis.updatedAt });

  return analysis.hypotheses;
}

export async function suggestHypothesesAction(form: FormData | { analysisId?: string; employeeUid?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const employeeUid = String(get('employeeUid') ?? '');
  const analysisId = String(get('analysisId') ?? '');
  if (!employeeUid || !analysisId) throw new Error('employeeUid and analysisId required');
  await suggestHypothesesForAnalysis(employeeUid, analysisId);
  return;
}

export async function submitAnalysis(employeeUid: string, analysisId: string) {
  await assertHasRole(employeeUid, 'employee');
  const snap = await adminFirestore.collection('analyses').doc(analysisId).get();
  if (!snap.exists) throw new Error('Analysis not found');
  const analysis = snap.data() as Analysis;
  if (analysis.author !== employeeUid) throw new Error('Not the author');
  if (analysis.status !== 'DRAFT') throw new Error('Only DRAFT can be submitted');

  // Recalculate readiness score using simple heuristics: (symptoms + signals + hypotheses)/3 normalized
  const score = Math.min(100, Math.round(((analysis.symptoms.length + analysis.signals.length + analysis.hypotheses.length) / 9) * 100));
  if (score < 75) throw new Error('Readiness score must be ≥ 75 to submit');

  const now = Date.now();
  const statusEntry: StatusHistoryEntry = { status: 'SUBMITTED', changedAt: now, changedBy: employeeUid };

  await adminFirestore.collection('analyses').doc(analysisId).update({ status: 'SUBMITTED', readinessScore: score, updatedAt: now, statusHistory: admin.firestore.FieldValue.arrayUnion(statusEntry) });

  return { success: true };
}

export async function submitAnalysisAction(form: FormData | { analysisId?: string; employeeUid?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const employeeUid = String(get('employeeUid') ?? '');
  const analysisId = String(get('analysisId') ?? '');
  if (!employeeUid || !analysisId) throw new Error('employeeUid and analysisId required');
  await submitAnalysis(employeeUid, analysisId);
  return;
}
export async function managerReviewAnalysis(managerUid: string, analysisId: string, action: { type: 'NEEDS_CHANGES' | 'APPROVE'; note?: string }) {
  await assertHasRole(managerUid, 'manager');
  const snap = await adminFirestore.collection('analyses').doc(analysisId).get();
  if (!snap.exists) throw new Error('Analysis not found');
  const analysis = snap.data() as Analysis;
  if (analysis.status !== 'SUBMITTED') throw new Error('Only SUBMITTED analyses can be reviewed');

  const now = Date.now();
  const status = action.type === 'APPROVE' ? 'APPROVED' : 'NEEDS_CHANGES';
  const statusEntry: StatusHistoryEntry = { status, changedAt: now, changedBy: managerUid, note: action.note };

  await adminFirestore.collection('analyses').doc(analysisId).update({ status, updatedAt: now, statusHistory: admin.firestore.FieldValue.arrayUnion(statusEntry) });

  // If approved, create a draft report (AI-only generates draft summary; manager must finalize)
  if (status === 'APPROVED') {
    const taskSnap = await adminFirestore.collection('tasks').doc(analysis.taskId).get();
    let taskTitle = analysis.taskId;
    if (taskSnap.exists) {
      const taskData = taskSnap.data() as Task;
      taskTitle = taskData.title ?? taskTitle;
    }

    const exec = await draftExecutiveSummary({ taskTitle, analysis: JSON.stringify(analysis) });

    const report = {
      taskId: analysis.taskId,
      author: managerUid,
      executiveSummaryDraft: { text: exec.summary, generatedAt: now, generatorModel: 'gemini-2.5-flash' },
      status: 'DRAFT',
      createdAt: now,
      updatedAt: now,
      statusHistory: [{ status: 'DRAFT', changedAt: now, changedBy: managerUid }],
    } as Report;

    const rRef = await adminFirestore.collection('reports').add(report);
    return { reportId: rRef.id };
  }

  return { success: true };
}

export async function managerReviewAction(form: FormData | { managerUid?: string; analysisId?: string; type?: string; note?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const managerUid = String(get('managerUid') ?? '');
  const analysisId = String(get('analysisId') ?? '');
  const type = String(get('type') ?? '');
  const note = String(get('note') ?? '');
  if (!managerUid || !analysisId || !type) throw new Error('managerUid, analysisId, and type are required');
  if (!['NEEDS_CHANGES', 'APPROVE'].includes(type)) throw new Error('Invalid action type');
  await managerReviewAnalysis(managerUid, analysisId, { type: type as 'NEEDS_CHANGES' | 'APPROVE', note });
  return;
}
export async function finalizeReport(managerUid: string, reportId: string) {
  await assertHasRole(managerUid, 'manager');

  const snap = await adminFirestore.collection('reports').doc(reportId).get();
  if (!snap.exists) throw new Error('Report not found');
  const report = snap.data() as Report;
  if (report.status !== 'DRAFT') throw new Error('Only DRAFT can be finalized');

  const now = Date.now();
  const statusEntry: StatusHistoryEntry = { status: 'FINALIZED', changedAt: now, changedBy: managerUid };

  await adminFirestore.collection('reports').doc(reportId).update({ status: 'FINALIZED', updatedAt: now, statusHistory: admin.firestore.FieldValue.arrayUnion(statusEntry) });

  return { success: true };
}

export async function getFinalizedReportForOwner(ownerUid: string, reportId: string) {
  await assertHasRole(ownerUid, 'owner');
  const snap = await adminFirestore.collection('reports').doc(reportId).get();
  if (!snap.exists) throw new Error('Report not found');
  const report = snap.data() as Report;
  if (report.status !== 'FINALIZED') throw new Error('Report is not finalized');
  return { id: snap.id, ...report };
}
export async function finalizeReportAction(form: FormData | { managerUid?: string; reportId?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const managerUid = String(get('managerUid') ?? '');
  const reportId = String(get('reportId') ?? '');
  if (!managerUid || !reportId) throw new Error('managerUid and reportId required');
  await finalizeReport(managerUid, reportId);
  return;
}