export type Role = 'EMPLOYEE' | 'MANAGER' | 'OWNER';

export type TimestampLike = { seconds: number; nanoseconds: number };

export type StatusHistoryEntry = {
  status: string;
  at: TimestampLike;
  by: string;
};

export type UserProfile = {
  uid: string;
  email: string;
  role: Role;
  displayName: string;
  createdAt: TimestampLike;
};

export type Task = {
  id?: string;
  title: string;
  description: string;
  assignedTo?: string;
  createdBy: string;
  status: 'OPEN' | 'IN_PROGRESS' | 'COMPLETED';
  createdAt: TimestampLike;
  updatedAt: TimestampLike;
  statusHistory: StatusHistoryEntry[];
};

export type Analysis = {
  id?: string;
  taskId: string;
  employeeId: string;
  status: 'DRAFT' | 'SUBMITTED' | 'NEEDS_CHANGES' | 'APPROVED';
  analysisType: 'LATENCY' | 'SECURITY' | 'OUTAGE' | 'CAPACITY';
  symptoms: string[];
  signals: string[];
  hypotheses: string[];
  readinessScore: number;
  managerFeedback?: string;
  revisionCount: number;
  statusHistory: StatusHistoryEntry[];
  createdAt: TimestampLike;
  updatedAt: TimestampLike;
};

export type Report = {
  id?: string;
  analysisId: string;
  summary: string;
  createdBy: string;
  createdAt: TimestampLike;
};