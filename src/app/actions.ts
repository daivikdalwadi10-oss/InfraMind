'use server';

import { callPhpApi } from '@/src/lib/api';
import { assertHasRole, createSessionCookie, clearSessionCookie, requireSessionUser, SessionUser } from '@/src/lib/auth';
import { Analysis, Role, Task } from '@/src/lib/types';

// Helper types for Form-like inputs
type FormLike = FormData | Record<string, unknown>;
const getValue = (form: FormLike, k: string) => (form instanceof FormData ? form.get(k) : (form as Record<string, unknown>)[k]);

export type ActionResult = { ok: boolean; message: string };
const toErrorMessage = (err: unknown) => (err instanceof Error ? err.message : String(err));

// Session management (login/logout)

export async function createSessionAction(form: FormData | { email?: string; password?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const email = String(get('email') ?? '');
  const password = String(get('password') ?? '');

  if (!email || !password) throw new Error('email and password required');

  const response = await callPhpApi('POST', '/auth/login', { email, password });
  if (!response.success) throw new Error(response.error || 'Login failed');

  const data = response.data as { user: { id: string; email: string; name: string; role: Role }; tokens: { accessToken: string; refreshToken: string } };
  const sessionUser: SessionUser = {
    uid: data.user.id,
    email: data.user.email,
    name: data.user.name,
    role: data.user.role,
  };

  await createSessionCookie(sessionUser);
  return sessionUser;
}

export async function signupAction(form: FormData | { email?: string; password?: string; name?: string; role?: Role }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const email = String(get('email') ?? '');
  const password = String(get('password') ?? '');
  const name = String(get('name') ?? '');
  const role = (String(get('role') ?? '').toUpperCase() as Role) || 'EMPLOYEE';

  if (!email || !password || !name) throw new Error('email, password, and name required');

  const response = await callPhpApi('POST', '/auth/signup', { email, password, name, role });
  if (!response.success) throw new Error(response.error || 'Signup failed');

  const data = response.data as { user: { id: string; email: string; name: string; role: Role }; tokens: { accessToken: string; refreshToken: string } };
  const sessionUser: SessionUser = {
    uid: data.user.id,
    email: data.user.email,
    name: data.user.name,
    role: data.user.role,
  };

  await createSessionCookie(sessionUser);
  return sessionUser;
}

export async function logoutAction() {
  await clearSessionCookie();
  return;
}

export async function getCurrentUserProfile() {
  const session = await requireSessionUser();
  return session;
}

// Task management

export async function createTask(payload: { title: string; description: string; assignedTo?: string }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const response = await callPhpApi('POST', '/tasks', { title: payload.title, description: payload.description, assignedTo: payload.assignedTo });
  if (!response.success) throw new Error(response.error || 'Failed to create task');

  return response.data;
}

export async function listTasksForManager(options?: { status?: Task['status']; limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const endpoint = `/tasks?${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch tasks');

  return response.data ?? [];
}

export async function createTaskAction(form: FormData | { title?: string; description?: string; assignedTo?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const payload = {
    title: String(get('title') ?? ''),
    description: String(get('description') ?? ''),
    assignedTo: String(get('assignedTo') ?? get('assignee') ?? '') || undefined,
  };
  if (!payload.title.trim()) throw new Error('title required');
  if (!payload.description.trim()) throw new Error('description required');
  await createTask(payload);
  return;
}

// Analysis management

export async function startAnalysis(taskId: string, analysisType: Analysis['analysisType']) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('POST', '/analyses', { taskId, analysisType });
  if (!response.success) throw new Error(response.error || 'Failed to create analysis');

  return response.data;
}

export async function listAnalysesForEmployee(options?: { status?: Analysis['status']; limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const endpoint = `/analyses?${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch analyses');

  return response.data ?? [];
}

export async function listTasksForEmployee(options?: { status?: Task['status']; limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const endpoint = `/tasks?${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch tasks');

  return response.data ?? [];
}

export async function startAnalysisAction(form: FormData | { taskId?: string; analysisType?: Analysis['analysisType'] }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const taskId = String(get('taskId') ?? '');
  const analysisType = String(get('analysisType') ?? '') as Analysis['analysisType'];
  if (!taskId || !analysisType) throw new Error('taskId and analysisType required');
  await startAnalysis(taskId, analysisType);
  return;
}

export async function suggestHypothesesForAnalysis(analysisId: string) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('POST', `/analyses/${analysisId}/suggest-hypotheses`, {});
  if (!response.success) throw new Error(response.error || 'Failed to suggest hypotheses');

  return response.data;
}

export async function suggestHypothesesAction(form: FormData | { analysisId?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const analysisId = String(get('analysisId') ?? '');
  if (!analysisId) throw new Error('analysisId required');
  await suggestHypothesesForAnalysis(analysisId);
  return;
}

export async function submitAnalysis(analysisId: string) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('POST', `/analyses/${analysisId}/submit`, {});
  if (!response.success) throw new Error(response.error || 'Failed to submit analysis');

  return { success: true };
}

export async function submitAnalysisAction(form: FormData | { analysisId?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const analysisId = String(get('analysisId') ?? '');
  if (!analysisId) throw new Error('analysisId required');
  await submitAnalysis(analysisId);
  return;
}

export async function getAnalysisForEmployee(analysisId: string) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('GET', `/analyses/${analysisId}`);
  if (!response.success) throw new Error(response.error || 'Analysis not found');

  return response.data;
}

export async function updateAnalysisContent(
  analysisId: string,
  payload: { symptoms?: string[]; signals?: string[]; hypotheses?: string[] }
) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('PUT', `/analyses/${analysisId}`, payload);
  if (!response.success) throw new Error(response.error || 'Failed to update analysis');

  return { success: true };
}

export async function updateAnalysisSectionAction(
  form: FormData | { analysisId?: string; section?: string; values?: string }
) {
  const get = (k: string) => getValue(form as FormLike, k);
  const analysisId = String(get('analysisId') ?? '');
  const section = String(get('section') ?? '');
  const values = String(get('values') ?? '');
  if (!analysisId || !section) throw new Error('analysisId and section required');
  const items = values
    .split('\n')
    .map((value) => value.trim())
    .filter(Boolean);

  const payload: Record<string, string[]> = {};
  if (section === 'symptoms') {
    payload.symptoms = items;
  } else if (section === 'signals') {
    payload.signals = items;
  } else if (section === 'hypotheses') {
    payload.hypotheses = items;
  } else {
    throw new Error('Invalid section');
  }

  await updateAnalysisContent(analysisId, payload);
  return;
}

// Manager review

export async function managerReviewAnalysis(analysisId: string, action: { type: 'NEEDS_CHANGES' | 'APPROVE'; feedback?: string }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const response = await callPhpApi('POST', `/analyses/${analysisId}/review`, {
    action: action.type === 'APPROVE' ? 'approve' : 'reject',
    feedback: action.feedback,
  });
  if (!response.success) throw new Error(response.error || 'Failed to review analysis');

  return response.data;
}

export async function listReportsForManager(options?: { limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const endpoint = `/reports?${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch reports');

  return response.data ?? [];
}

export async function listSubmittedAnalysesForManager(options?: { limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const endpoint = `/analyses?status=SUBMITTED&${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch analyses');

  return response.data ?? [];
}

export async function managerReviewAction(form: FormData | { analysisId?: string; type?: string; feedback?: string }) {
  const get = (k: string) => getValue(form as FormLike, k);
  const analysisId = String(get('analysisId') ?? '');
  const type = String(get('type') ?? '');
  const feedback = String(get('feedback') ?? get('note') ?? '');
  if (!analysisId || !type) throw new Error('analysisId and type are required');
  if (!['NEEDS_CHANGES', 'APPROVE'].includes(type)) throw new Error('Invalid action type');
  await managerReviewAnalysis(analysisId, { type: type as 'NEEDS_CHANGES' | 'APPROVE', feedback });
  return;
}

// Owner reports

export async function getReportForOwner(reportId: string) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'OWNER');

  const response = await callPhpApi('GET', `/reports/${reportId}`);
  if (!response.success) throw new Error(response.error || 'Report not found');

  return response.data;
}

export async function listReportsForOwner(options?: { limit?: number }) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'OWNER');

  const endpoint = `/reports?${new URLSearchParams({ limit: String(options?.limit ?? 20) }).toString()}`;
  const response = await callPhpApi('GET', endpoint);
  if (!response.success) throw new Error(response.error || 'Failed to fetch reports');

  return response.data ?? [];
}

// UI-friendly wrappers (return ActionResult for client forms)
export async function createTaskForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await createTaskAction(formData);
    return { ok: true, message: 'Task created.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}

export async function startAnalysisForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await startAnalysisAction(formData);
    return { ok: true, message: 'Analysis started.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}

export async function suggestHypothesesForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await suggestHypothesesAction(formData);
    return { ok: true, message: 'Hypotheses suggested.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}

export async function submitAnalysisForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await submitAnalysisAction(formData);
    return { ok: true, message: 'Analysis submitted.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}

export async function updateAnalysisSectionForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await updateAnalysisSectionAction(formData);
    return { ok: true, message: 'Analysis updated.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}

export async function managerReviewForm(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  try {
    await managerReviewAction(formData);
    return { ok: true, message: 'Review submitted.' };
  } catch (err) {
    return { ok: false, message: toErrorMessage(err) };
  }
}