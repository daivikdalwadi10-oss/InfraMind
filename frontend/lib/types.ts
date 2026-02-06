export type Role = 'EMPLOYEE' | 'MANAGER' | 'OWNER';

export interface UserProfile {
  id: string;
  email: string;
  displayName: string;
  role: Role;
}

export type TaskStatus = 'OPEN' | 'IN_PROGRESS' | 'COMPLETED';

export interface Task {
  id: string;
  title: string;
  description?: string;
  status: TaskStatus;
  assignedTo?: string;
  createdBy?: string;
  createdAt?: string;
  updatedAt?: string;
}

export type AnalysisStatus =
  | 'DRAFT'
  | 'NEEDS_CHANGES'
  | 'SUBMITTED'
  | 'APPROVED';

export interface Hypothesis {
  text: string;
  confidence: number;
  evidence: string[];
}

export interface Analysis {
  id: string;
  taskId: string;
  status: AnalysisStatus;
  analysisType?: string;
  symptoms?: string[];
  signals?: string[];
  hypotheses?: Hypothesis[];
  readinessScore?: number;
  managerFeedback?: string;
  revisionCount?: number;
  createdAt?: string;
  updatedAt?: string;
  employeeId?: string;
}

export interface Report {
  id: string;
  analysisId?: string;
  analysis_id?: string;
  summary?: string;
  createdBy?: string;
  created_by?: string;
  createdAt?: string;
  created_at?: string;
  updatedAt?: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
}
