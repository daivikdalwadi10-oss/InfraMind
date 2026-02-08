export type Role = 'EMPLOYEE' | 'MANAGER' | 'OWNER' | 'DEVELOPER' | 'SYSTEM_ADMIN';

export interface UserProfile {
  id: string;
  email: string;
  displayName: string;
  role: Role;
  position?: string | null;
  teams?: string | null;
  active_analysis_count?: number;
  active_analysis_ids?: string | null;
}

export type TaskStatus = 'OPEN' | 'IN_PROGRESS' | 'COMPLETED';

export type IncidentSeverity = 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
export type IncidentStatus = 'OPEN' | 'INVESTIGATING' | 'RESOLVED';
export type InfrastructureStatus = 'HEALTHY' | 'DEGRADED' | 'OUTAGE' | 'MAINTENANCE';
export type RiskSeverity = 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
export type RiskStatus = 'OPEN' | 'MITIGATING' | 'RESOLVED';
export type MeetingStatus = 'SCHEDULED' | 'COMPLETED' | 'CANCELLED';

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
  | 'APPROVED'
  | 'REPORT_GENERATED';

export interface Hypothesis {
  text: string;
  confidence: number;
  evidence: string[];
}

export interface EnvironmentContext {
  cloudProvider?: string;
  region?: string;
  serviceType?: string;
  deploymentVersion?: string;
}

export interface TimelineEvents {
  deployments?: string[];
  configChanges?: string[];
  trafficSpikes?: string[];
  alerts?: string[];
}

export interface DependencyImpact {
  upstreamServices?: string[];
  downstreamServices?: string[];
  sharedInfrastructure?: string[];
}

export interface RiskClassification {
  customerImpact?: string;
  slaImpact?: string;
  severityLevel?: RiskSeverity;
}

export interface Analysis {
  id: string;
  taskId: string;
  title?: string;
  status: AnalysisStatus;
  analysisType?: string;
  symptoms?: string[];
  signals?: string[];
  hypotheses?: Hypothesis[];
  environmentContext?: EnvironmentContext;
  timelineEvents?: TimelineEvents;
  dependencyImpact?: DependencyImpact;
  riskClassification?: RiskClassification;
  readinessScore?: number;
  managerFeedback?: string;
  revisionCount?: number;
  createdAt?: string;
  updatedAt?: string;
  employeeId?: string;
  createdBy?: string;
  teamId?: string | null;
}

export interface Report {
  id: string;
  analysisId?: string;
  analysis_id?: string;
  summary?: string;
  executiveSummary?: string | null;
  rootCause?: string | null;
  impact?: string | null;
  resolution?: string | null;
  preventionSteps?: string | null;
  aiAssisted?: boolean;
  status?: 'DRAFT' | 'FINALIZED';
  createdBy?: string;
  created_by?: string;
  createdAt?: string;
  created_at?: string;
  updatedAt?: string;
  updated_at?: string;
}

export type AiOutputStatus = 'GENERATED' | 'ACCEPTED' | 'REJECTED' | 'EDITED';
export type AiOutputType = 'HYPOTHESES' | 'REPORT_DRAFT';

export interface AiOutput {
  id: string;
  analysisId: string;
  outputType: AiOutputType;
  generatedBy: string;
  status: AiOutputStatus;
  payload: Record<string, unknown>;
  createdAt: string;
}

export interface Team {
  id: string;
  name: string;
  description?: string | null;
  managerId: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface Incident {
  id: string;
  title: string;
  description?: string | null;
  severity: IncidentSeverity;
  status: IncidentStatus;
  reportedBy: string;
  assignedTo?: string | null;
  occurredAt?: string | null;
  createdAt?: string;
  updatedAt?: string;
  resolvedAt?: string | null;
}

export interface InfrastructureState {
  id: string;
  component: string;
  status: InfrastructureStatus;
  summary?: string | null;
  observedAt: string;
  reportedBy: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface ArchitectureRisk {
  id: string;
  title: string;
  description?: string | null;
  severity: RiskSeverity;
  status: RiskStatus;
  ownerId: string;
  analysisId?: string | null;
  createdAt?: string;
  updatedAt?: string;
  resolvedAt?: string | null;
}

export interface Meeting {
  id: string;
  title: string;
  agenda?: string | null;
  status: MeetingStatus;
  scheduledAt: string;
  durationMinutes: number;
  organizerId: string;
  analysisId?: string | null;
  incidentId?: string | null;
  createdAt?: string;
  updatedAt?: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export type AdminHealthStatus = 'UP' | 'DEGRADED' | 'DOWN';

export interface AdminHealth {
  status: AdminHealthStatus;
  api: string;
  database: string;
  environment: string;
  lastDeployment: string;
  maintenanceEnabled: boolean;
  softShutdownEnabled: boolean;
}

export interface AdminInsights {
  activeUsers: number;
  activeAnalyses: number;
  pendingApprovals: number;
  aiUsageLast24h: number;
  errorTrend: Array<{ day: string; count: number }>;
}

export interface AdminLogEntry {
  id?: string;
  timestamp?: string;
  level?: string;
  message?: string;
  context?: string | null;
  extra?: string | null;
  source?: string;
  entity_type?: string;
  entity_id?: string;
  action?: string;
  user_id?: string | null;
  user_role?: string | null;
  user_email?: string | null;
  created_at?: string;
  status?: string;
  details?: string | null;
  analysis_id?: string;
  changed_at?: string;
  changed_by?: string | null;
}

export interface MaintenanceState {
  maintenanceEnabled: boolean;
  maintenanceMessage?: string | null;
  softShutdownEnabled: boolean;
  lastRestartRequestedAt?: string | null;
  updatedAt?: string | null;
}

export type AnnouncementSeverity = 'INFO' | 'WARNING' | 'CRITICAL';

export interface Announcement {
  id: string;
  title: string;
  message: string;
  severity: AnnouncementSeverity;
  target_roles?: string;
  starts_at?: string | null;
  ends_at?: string | null;
  dismissible?: number | boolean;
  status?: 'ACTIVE' | 'ARCHIVED';
  created_by?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ServiceCredential {
  id: string;
  name: string;
  description?: string | null;
  status: 'ACTIVE' | 'ROTATED' | 'DISABLED';
  masked_value: string;
  created_by?: string;
  created_at?: string;
  updated_at?: string;
  last_rotated_at?: string | null;
}

export interface FeatureFlag {
  id: string;
  flag_key: string;
  description?: string | null;
  enabled: number | boolean;
  updated_by?: string | null;
  created_at?: string;
  updated_at?: string;
}
