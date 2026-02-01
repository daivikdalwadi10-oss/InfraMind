export type Role = 'employee' | 'manager' | 'owner';

export type StatusHistoryEntry = {
  status: string;
  changedAt: number; // epoch ms
  changedBy: string; // uid
  note?: string;
};

export type UserProfile = {
  uid: string;
  displayName: string;
  email: string;
  role: Role;
  createdAt: number;
  updatedAt: number;
  statusHistory: StatusHistoryEntry[];
};

export type Task = {
  id?: string;
  title: string;
  description: string;
  creator: string; // uid of manager who created
  assignee?: string; // uid of employee
  status: 'OPEN' | 'ASSIGNED' | 'CLOSED';
  createdAt: number;
  updatedAt: number;
  statusHistory: StatusHistoryEntry[];
};

export type Analysis = {
  id?: string;
  taskId: string;
  author: string; // employee uid
  symptoms: string[];
  signals: string[];
  hypotheses: { text: string; confidence?: number }[];
  readinessScore: number; // 0-100
  status: 'DRAFT' | 'SUBMITTED' | 'NEEDS_CHANGES' | 'APPROVED';
  createdAt: number;
  updatedAt: number;
  statusHistory: StatusHistoryEntry[];
};

export type Report = {
  id?: string;
  taskId: string;
  author: string; // manager uid
  executiveSummaryDraft?: {
    text: string;
    generatedAt: number;
    generatorModel?: string;
  };
  status: 'DRAFT' | 'FINALIZED';
  createdAt: number;
  updatedAt: number;
  statusHistory: StatusHistoryEntry[];
};