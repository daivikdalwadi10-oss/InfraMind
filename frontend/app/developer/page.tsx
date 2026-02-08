"use client";

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import {
  Activity,
  AlertTriangle,
  ClipboardList,
  Database,
  FileText,
  Lock,
  RefreshCcw,
  Server,
  ShieldCheck,
  SlidersHorizontal,
  Wrench,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type {
  AdminHealth,
  AdminInsights,
  AdminLogEntry,
  Announcement,
  FeatureFlag,
  MaintenanceState,
  ServiceCredential,
  Team,
  UserProfile,
} from '@/lib/types';

const adminRoles = new Set(['DEVELOPER', 'SYSTEM_ADMIN']);

type TabKey =
  | 'overview'
  | 'logs'
  | 'maintenance'
  | 'announcements'
  | 'credentials'
  | 'server'
  | 'panels';

const tabs: Array<{ key: TabKey; label: string; icon: React.ComponentType<{ className?: string }> }> = [
  { key: 'overview', label: 'Overview', icon: Activity },
  { key: 'logs', label: 'Logs', icon: FileText },
  { key: 'maintenance', label: 'Maintenance', icon: AlertTriangle },
  { key: 'announcements', label: 'Announcements', icon: ClipboardList },
  { key: 'credentials', label: 'Credentials', icon: Lock },
  { key: 'server', label: 'Server Control', icon: Server },
  { key: 'panels', label: 'Admin Panels', icon: SlidersHorizontal },
];

export default function DeveloperConsolePage() {
  const { user, status, accessToken } = useSession();
  const [activeTab, setActiveTab] = useState<TabKey>('overview');
  const [health, setHealth] = useState<AdminHealth | null>(null);
  const [insights, setInsights] = useState<AdminInsights | null>(null);
  const [maintenance, setMaintenance] = useState<MaintenanceState | null>(null);
  const [logs, setLogs] = useState<AdminLogEntry[]>([]);
  const [logType, setLogType] = useState('audit');
  const [logStart, setLogStart] = useState('');
  const [logEnd, setLogEnd] = useState('');
  const [logUser, setLogUser] = useState('');
  const [logRole, setLogRole] = useState('');
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [credentials, setCredentials] = useState<ServiceCredential[]>([]);
  const [users, setUsers] = useState<UserProfile[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [flags, setFlags] = useState<FeatureFlag[]>([]);
  const [error, setError] = useState<string | null>(null);

  const isAdmin = useMemo(() => (user?.role ? adminRoles.has(user.role) : false), [user?.role]);

  useEffect(() => {
    if (status !== 'authenticated' || !isAdmin) return;

    const loadForTab = async () => {
      switch (activeTab) {
        case 'overview':
          await loadOverview();
          break;
        case 'logs':
          await loadLogs();
          break;
        case 'maintenance':
          await loadMaintenance();
          break;
        case 'announcements':
          await loadAnnouncements();
          break;
        case 'credentials':
          await loadCredentials();
          break;
        case 'server':
          await loadMaintenance();
          break;
        case 'panels':
          await loadPanels();
          break;
        default:
          break;
      }
    };

    void loadForTab();
  }, [status, isAdmin, activeTab]);

  const loadOverview = async () => {
    const [healthRes, insightsRes] = await Promise.all([
      apiRequest<AdminHealth>('GET', '/admin/health', undefined, accessToken),
      apiRequest<AdminInsights>('GET', '/admin/insights', undefined, accessToken),
    ]);

    if (healthRes.success && healthRes.data) setHealth(healthRes.data);
    if (insightsRes.success && insightsRes.data) setInsights(insightsRes.data);
  };

  const loadMaintenance = async () => {
    const response = await apiRequest<MaintenanceState>('GET', '/admin/maintenance', undefined, accessToken);
    if (response.success && response.data) setMaintenance(response.data);
  };

  const loadLogs = async (type = logType) => {
    const params = new URLSearchParams({ type });
    if (logStart) params.set('start', logStart);
    if (logEnd) params.set('end', logEnd);
    if (logUser) params.set('user', logUser);
    if (logRole) params.set('role', logRole);
    const response = await apiRequest<AdminLogEntry[]>(
      'GET',
      `/admin/logs?${params.toString()}`,
      undefined,
      accessToken,
    );
    if (response.success && response.data) setLogs(response.data);
  };

  const loadAnnouncements = async () => {
    const response = await apiRequest<Announcement[]>('GET', '/admin/announcements', undefined, accessToken);
    if (response.success && response.data) setAnnouncements(response.data);
  };

  const loadCredentials = async () => {
    const response = await apiRequest<ServiceCredential[]>('GET', '/admin/credentials', undefined, accessToken);
    if (response.success && response.data) setCredentials(response.data);
  };

  const loadPanels = async () => {
    const [usersRes, teamsRes, flagsRes] = await Promise.all([
      apiRequest<UserProfile[]>('GET', '/admin/users', undefined, accessToken),
      apiRequest<Team[]>('GET', '/admin/teams', undefined, accessToken),
      apiRequest<FeatureFlag[]>('GET', '/admin/feature-flags', undefined, accessToken),
    ]);

    if (usersRes.success && usersRes.data) setUsers(usersRes.data);
    if (teamsRes.success && teamsRes.data) setTeams(teamsRes.data);
    if (flagsRes.success && flagsRes.data) setFlags(flagsRes.data);
  };

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="text-sm text-muted">Loading developer console...</p>
      </div>
    );
  }

  if (status !== 'authenticated') {
    return (
      <div className="flex min-h-screen items-center justify-center px-6">
        <Card className="w-full max-w-md">
          <CardHeader>
            <CardTitle>Developer access required</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-muted">Sign in with a developer or system admin account to continue.</p>
            <Button asChild>
              <Link href="/login">Go to login</Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!isAdmin) {
    return (
      <div className="flex min-h-screen items-center justify-center px-6">
        <Card className="w-full max-w-md border-rose-200 bg-rose-50/60">
          <CardHeader>
            <CardTitle>Access denied</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-rose-700">This console is restricted to developer and system admin roles.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-surface px-6 py-10 text-ink">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-8 lg:flex-row">
        <aside className="w-full max-w-xs space-y-2 rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm">
          <div className="px-2 pb-2">
            <p className="text-xs uppercase tracking-[0.3em] text-muted">Developer Console</p>
            <h1 className="mt-2 text-xl font-semibold text-ink">Platform Admin</h1>
            <p className="mt-1 text-xs text-muted">Internal operations and system control</p>
          </div>
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.key;
            return (
              <button
                key={tab.key}
                className={`flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ${
                  isActive ? 'bg-ink text-white' : 'text-ink hover:bg-slate-100'
                }`}
                onClick={() => setActiveTab(tab.key)}
              >
                <Icon className="h-4 w-4" />
                {tab.label}
              </button>
            );
          })}
        </aside>

        <section className="flex-1 space-y-6">
          {error ? <p className="text-sm text-rose-600">{error}</p> : null}

          {activeTab === 'overview' ? (
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>System status</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <p className="text-xs uppercase text-muted">Application status</p>
                    <p className="text-lg font-semibold text-ink">{health?.status ?? '...'}</p>
                    <p className="text-xs text-muted">Environment: {health?.environment ?? 'n/a'}</p>
                  </div>
                  <div className="space-y-2">
                    <p className="text-xs uppercase text-muted">Dependencies</p>
                    <p className="text-sm text-ink">API: {health?.api ?? '...'}</p>
                    <p className="text-sm text-ink">Database: {health?.database ?? '...'}</p>
                    <p className="text-xs text-muted">Last deploy: {health?.lastDeployment ?? 'n/a'}</p>
                  </div>
                </CardContent>
              </Card>

              <div className="grid gap-4 md:grid-cols-4">
                <Card className="p-4">
                  <p className="text-xs uppercase text-muted">Active users</p>
                  <p className="mt-2 text-2xl font-semibold text-ink">{insights?.activeUsers ?? 0}</p>
                </Card>
                <Card className="p-4">
                  <p className="text-xs uppercase text-muted">Active analyses</p>
                  <p className="mt-2 text-2xl font-semibold text-ink">{insights?.activeAnalyses ?? 0}</p>
                </Card>
                <Card className="p-4">
                  <p className="text-xs uppercase text-muted">Pending approvals</p>
                  <p className="mt-2 text-2xl font-semibold text-ink">{insights?.pendingApprovals ?? 0}</p>
                </Card>
                <Card className="p-4">
                  <p className="text-xs uppercase text-muted">AI usage (24h)</p>
                  <p className="mt-2 text-2xl font-semibold text-ink">{insights?.aiUsageLast24h ?? 0}</p>
                </Card>
              </div>
            </div>
          ) : null}

          {activeTab === 'logs' ? (
            <Card>
              <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <CardTitle>Centralized log viewer</CardTitle>
                <div className="flex flex-wrap gap-2">
                  {['audit', 'auth', 'workflow', 'ai', 'app', 'error'].map((type) => (
                    <Button
                      key={type}
                      variant="secondary"
                      size="sm"
                      onClick={() => {
                        setLogType(type);
                        void loadLogs(type);
                      }}
                    >
                      {type}
                    </Button>
                  ))}
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs md:grid-cols-2">
                  <select
                    className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                    value={logType}
                    onChange={(event) => setLogType(event.target.value)}
                  >
                    <option value="audit">Audit</option>
                    <option value="auth">Auth/IAM</option>
                    <option value="workflow">Workflow</option>
                    <option value="ai">AI</option>
                    <option value="app">Application</option>
                    <option value="error">Errors</option>
                  </select>
                  <input
                    className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                    placeholder="Role filter (MANAGER, OWNER)"
                    value={logRole}
                    onChange={(event) => setLogRole(event.target.value)}
                  />
                  <input
                    className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                    placeholder="User ID filter"
                    value={logUser}
                    onChange={(event) => setLogUser(event.target.value)}
                  />
                  <div className="grid gap-2 sm:grid-cols-2">
                    <input
                      className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                      placeholder="Start (YYYY-MM-DD)"
                      value={logStart}
                      onChange={(event) => setLogStart(event.target.value)}
                    />
                    <input
                      className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                      placeholder="End (YYYY-MM-DD)"
                      value={logEnd}
                      onChange={(event) => setLogEnd(event.target.value)}
                    />
                  </div>
                  <Button size="sm" variant="secondary" onClick={() => void loadLogs(logType)}>
                    Apply filters
                  </Button>
                </div>
                {logs.length === 0 ? <p className="text-sm text-muted">No logs available.</p> : null}
                {logs.map((entry, index) => (
                  <div
                    key={`${entry.id ?? entry.timestamp ?? 'log'}-${index}`}
                    className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-ink"
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                      <span>{entry.timestamp || entry.created_at || entry.changed_at}</span>
                      <span>{entry.level || entry.action || entry.status}</span>
                      <span>{entry.user_email || entry.user_id || entry.source}</span>
                    </div>
                    <p className="mt-2 text-sm text-ink">
                      {entry.message || entry.details || entry.action || entry.entity_type}
                    </p>
                  </div>
                ))}
              </CardContent>
            </Card>
          ) : null}

          {activeTab === 'maintenance' ? (
            <Card>
              <CardHeader>
                <CardTitle>Maintenance mode</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-xs uppercase text-muted">Message</label>
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={maintenance?.maintenanceMessage ?? ''}
                    onChange={(event) =>
                      setMaintenance((prev) => ({
                        ...(prev ?? { maintenanceEnabled: false, softShutdownEnabled: false }),
                        maintenanceMessage: event.target.value,
                      }))
                    }
                  />
                </div>
                <div className="flex flex-wrap gap-2">
                  <Button
                    onClick={async () => {
                      const response = await apiRequest<MaintenanceState>(
                        'PUT',
                        '/admin/maintenance',
                        {
                          maintenanceEnabled: true,
                          maintenanceMessage: maintenance?.maintenanceMessage ?? '',
                        },
                        accessToken,
                      );
                      if (response.success && response.data) setMaintenance(response.data);
                    }}
                  >
                    Enable maintenance
                  </Button>
                  <Button
                    variant="secondary"
                    onClick={async () => {
                      const response = await apiRequest<MaintenanceState>(
                        'PUT',
                        '/admin/maintenance',
                        { maintenanceEnabled: false, maintenanceMessage: '' },
                        accessToken,
                      );
                      if (response.success && response.data) setMaintenance(response.data);
                    }}
                  >
                    Disable maintenance
                  </Button>
                </div>
                <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-muted">
                  Maintenance blocks all non-admin traffic and surfaces the message above.
                </div>
              </CardContent>
            </Card>
          ) : null}

          {activeTab === 'announcements' ? (
            <Card>
              <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <CardTitle>System announcements</CardTitle>
                <Button size="sm" variant="secondary" onClick={() => void loadAnnouncements()}>
                  <RefreshCcw className="mr-2 h-4 w-4" />
                  Refresh
                </Button>
              </CardHeader>
              <CardContent className="space-y-4">
                <AnnouncementForm
                  token={accessToken}
                  onCreated={() => void loadAnnouncements()}
                  setError={setError}
                />
                <div className="space-y-2">
                  {announcements.map((item) => (
                    <div key={item.id} className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                      <div className="flex items-center justify-between text-xs text-muted">
                        <span>{item.severity}</span>
                        <span>{item.status}</span>
                      </div>
                      <p className="mt-1 text-sm font-semibold text-ink">{item.title}</p>
                      <p className="text-xs text-muted">{item.message}</p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          ) : null}

          {activeTab === 'credentials' ? (
            <Card>
              <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <CardTitle>Credential management</CardTitle>
                <Button size="sm" variant="secondary" onClick={() => void loadCredentials()}>
                  <RefreshCcw className="mr-2 h-4 w-4" />
                  Refresh
                </Button>
              </CardHeader>
              <CardContent className="space-y-4">
                <CredentialForm
                  token={accessToken}
                  onCreated={() => void loadCredentials()}
                  setError={setError}
                />
                <div className="space-y-2">
                  {credentials.map((item) => (
                    <div key={item.id} className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                      <div className="flex items-center justify-between text-xs text-muted">
                        <span>{item.status}</span>
                        <span>{item.masked_value}</span>
                      </div>
                      <p className="mt-1 text-sm font-semibold text-ink">{item.name}</p>
                      <p className="text-xs text-muted">{item.description}</p>
                      <div className="mt-3 flex flex-wrap gap-2">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={async () => {
                            const secret = window.prompt('Enter new secret value');
                            if (!secret) return;
                            const confirmRotate = window.confirm('Rotate this credential?');
                            if (!confirmRotate) return;
                            await apiRequest('POST', `/admin/credentials/${item.id}/rotate`, { secret }, accessToken);
                            await loadCredentials();
                          }}
                        >
                          Rotate
                        </Button>
                        <Button
                          size="sm"
                          variant="destructive"
                          onClick={async () => {
                            const confirmDisable = window.confirm('Disable this credential?');
                            if (!confirmDisable) return;
                            await apiRequest('POST', `/admin/credentials/${item.id}/disable`, {}, accessToken);
                            await loadCredentials();
                          }}
                        >
                          Disable
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          ) : null}

          {activeTab === 'server' ? (
            <Card>
              <CardHeader>
                <CardTitle>Manual server control</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <ServerControlForm token={accessToken} onAction={loadMaintenance} setError={setError} />
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                  All server actions are logged and require explicit confirmation.
                </div>
              </CardContent>
            </Card>
          ) : null}

          {activeTab === 'panels' ? (
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>User management (view only)</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {users.map((entry) => (
                    <div key={entry.id} className="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                      <p className="text-sm font-semibold text-ink">{entry.displayName}</p>
                      <p className="text-xs text-muted">{entry.email} · {entry.role}</p>
                    </div>
                  ))}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Team overview</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {teams.map((team) => (
                    <div key={team.id} className="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                      <p className="text-sm font-semibold text-ink">{team.name}</p>
                      <p className="text-xs text-muted">{team.description}</p>
                    </div>
                  ))}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Feature flags</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {flags.length === 0 ? <p className="text-sm text-muted">No flags configured.</p> : null}
                  {flags.map((flag) => (
                    <div key={flag.id} className="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-semibold text-ink">{flag.flag_key}</p>
                        <span className="text-xs text-muted">{flag.enabled ? 'Enabled' : 'Disabled'}</span>
                      </div>
                      <p className="text-xs text-muted">{flag.description}</p>
                    </div>
                  ))}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Permission inspection</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3 text-xs text-muted md:grid-cols-3">
                  <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
                    <ShieldCheck className="h-4 w-4" />
                    <p className="mt-2 text-sm font-semibold text-ink">Role assignments</p>
                    <p className="mt-1">Changes are restricted to backend policies.</p>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
                    <Database className="h-4 w-4" />
                    <p className="mt-2 text-sm font-semibold text-ink">Access boundaries</p>
                    <p className="mt-1">IAM rules enforced on all admin endpoints.</p>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
                    <Wrench className="h-4 w-4" />
                    <p className="mt-2 text-sm font-semibold text-ink">Operational controls</p>
                    <p className="mt-1">Actions require confirmation and logging.</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          ) : null}
        </section>
      </div>
    </div>
  );
}

function AnnouncementForm({
  token,
  onCreated,
  setError,
}: {
  token: string | null;
  onCreated: () => void;
  setError: (_value: string | null) => void;
}) {
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [severity, setSeverity] = useState<'INFO' | 'WARNING' | 'CRITICAL'>('INFO');
  const [targets, setTargets] = useState('ALL');
  const [dismissible, setDismissible] = useState(true);

  const handleCreate = async () => {
    const response = await apiRequest<Announcement>(
      'POST',
      '/admin/announcements',
      { title, message, severity, targetRoles: targets, dismissible },
      token,
    );

    if (!response.success) {
      setError(response.error || 'Failed to create announcement');
      return;
    }

    setTitle('');
    setMessage('');
    setError(null);
    onCreated();
  };

  return (
    <div className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
      <div className="grid gap-3 md:grid-cols-2">
        <input
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Title"
          value={title}
          onChange={(event) => setTitle(event.target.value)}
        />
        <select
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          value={severity}
          onChange={(event) => setSeverity(event.target.value as 'INFO' | 'WARNING' | 'CRITICAL')}
        >
          <option value="INFO">Info</option>
          <option value="WARNING">Warning</option>
          <option value="CRITICAL">Critical</option>
        </select>
      </div>
      <textarea
        className="min-h-[90px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
        placeholder="Announcement message"
        value={message}
        onChange={(event) => setMessage(event.target.value)}
      />
      <div className="grid gap-3 md:grid-cols-3">
        <input
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Targets (ALL, MANAGER, EMPLOYEE)"
          value={targets}
          onChange={(event) => setTargets(event.target.value)}
        />
        <label className="flex items-center gap-2 text-xs text-muted">
          <input type="checkbox" checked={dismissible} onChange={(event) => setDismissible(event.target.checked)} />
          Dismissible
        </label>
        <Button size="sm" onClick={handleCreate}>
          Publish announcement
        </Button>
      </div>
    </div>
  );
}

function CredentialForm({
  token,
  onCreated,
  setError,
}: {
  token: string | null;
  onCreated: () => void;
  setError: (_value: string | null) => void;
}) {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [secret, setSecret] = useState('');

  const handleCreate = async () => {
    const response = await apiRequest<ServiceCredential>(
      'POST',
      '/admin/credentials',
      { name, description, secret },
      token,
    );

    if (!response.success) {
      setError(response.error || 'Failed to create credential');
      return;
    }

    setName('');
    setDescription('');
    setSecret('');
    setError(null);
    onCreated();
  };

  return (
    <div className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
      <div className="grid gap-3 md:grid-cols-2">
        <input
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Credential name"
          value={name}
          onChange={(event) => setName(event.target.value)}
        />
        <input
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Description"
          value={description}
          onChange={(event) => setDescription(event.target.value)}
        />
      </div>
      <input
        className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
        placeholder="Secret value"
        type="password"
        value={secret}
        onChange={(event) => setSecret(event.target.value)}
      />
      <Button size="sm" onClick={handleCreate}>
        Add credential
      </Button>
    </div>
  );
}

function ServerControlForm({
  token,
  onAction,
  setError,
}: {
  token: string | null;
  onAction: () => Promise<void> | void;
  setError: (_value: string | null) => void;
}) {
  const [reason, setReason] = useState('');

  const runAction = async (action: 'SOFT_SHUTDOWN' | 'RESUME' | 'RESTART') => {
    const confirmed = window.confirm(`Confirm ${action.replace('_', ' ').toLowerCase()}?`);
    if (!confirmed) return;

    const response = await apiRequest('POST', '/admin/server-actions', { action, reason }, token);
    if (!response.success) {
      setError(response.error || 'Failed to run action');
      return;
    }

    setError(null);
    await onAction();
  };

  return (
    <div className="grid gap-3">
      <div className="space-y-2">
        <label className="text-xs uppercase text-muted">Reason</label>
        <input
          className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
          value={reason}
          onChange={(event) => setReason(event.target.value)}
          placeholder="Reason for operational action"
        />
      </div>
      <div className="flex flex-wrap gap-2">
        <Button size="sm" variant="secondary" onClick={() => void runAction('SOFT_SHUTDOWN')}>
          Soft shutdown
        </Button>
        <Button size="sm" variant="secondary" onClick={() => void runAction('RESUME')}>
          Resume service
        </Button>
        <Button size="sm" onClick={() => void runAction('RESTART')}>
          Request restart
        </Button>
      </div>
    </div>
  );
}
