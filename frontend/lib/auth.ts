import { Analysis, AnalysisStatus, Role, UserProfile } from './types';

export interface SessionState {
  user: UserProfile | null;
  accessToken: string | null;
}

const ACCESS_TOKEN_KEY = 'inframind.accessToken';

export function getAccessToken() {
  if (typeof window === 'undefined') return null;
  return window.localStorage.getItem(ACCESS_TOKEN_KEY);
}

export function setAccessToken(token: string) {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem(ACCESS_TOKEN_KEY, token);
}

export function clearAccessToken() {
  if (typeof window === 'undefined') return;
  window.localStorage.removeItem(ACCESS_TOKEN_KEY);
}

export function getRole(user: UserProfile | null): Role | null {
  return user?.role ?? null;
}

export function canEditAnalysis(role: Role | null, status: AnalysisStatus) {
  if (role !== 'EMPLOYEE') return false;
  return status === 'DRAFT' || status === 'NEEDS_CHANGES';
}

export function canSubmitAnalysis(role: Role | null, analysis: Analysis) {
  if (!canEditAnalysis(role, analysis.status)) return false;
  return (analysis.readinessScore ?? 0) >= 75;
}

export function canReviewAnalysis(role: Role | null, status: AnalysisStatus) {
  return role === 'MANAGER' && status === 'SUBMITTED';
}

export function canGenerateReport(role: Role | null, status: AnalysisStatus) {
  return role === 'MANAGER' && status === 'APPROVED';
}
