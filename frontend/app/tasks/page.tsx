'use client';

import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Analysis, Task, Team, UserProfile } from '@/lib/types';

export default function TasksPage() {
  const { user, accessToken, status } = useSession();
  const [tasks, setTasks] = useState<Task[]>([]);
  const [analyses, setAnalyses] = useState<Analysis[]>([]);
  const [users, setUsers] = useState<UserProfile[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [assignedTo, setAssignedTo] = useState('');
  const [analysisTitle, setAnalysisTitle] = useState('');
  const [analysisType, setAnalysisType] = useState('LATENCY');
  const [analysisAssignedTo, setAnalysisAssignedTo] = useState('');
  const [analysisTeamId, setAnalysisTeamId] = useState('');
  const [analysisDescription, setAnalysisDescription] = useState('');
  const [teams, setTeams] = useState<Team[]>([]);

  const role = user?.role ?? null;
  const isEmployee = role === 'EMPLOYEE';
  const canAssign = role === 'MANAGER' || role === 'OWNER';

  const loadTasks = async () => {
    if (status !== 'authenticated' || !accessToken) return;
    setLoading(true);
    setError(null);
    const response = await apiRequest<Task[]>('GET', '/tasks', undefined, accessToken);
    if (!response.success || !response.data) {
      setError(response.error || 'Unable to load tasks.');
      setTasks([]);
      setLoading(false);
      return;
    }
    setTasks(response.data);
    setLoading(false);
  };

  const loadAnalyses = async () => {
    if (!isEmployee || status !== 'authenticated' || !accessToken) return;
    const response = await apiRequest<Analysis[]>('GET', '/analyses', undefined, accessToken);
    if (!response.success || !response.data) {
      setAnalyses([]);
      return;
    }
    setAnalyses(response.data);
  };

  const loadUsers = async () => {
    if (!canAssign || status !== 'authenticated' || !accessToken) return;
    const response = await apiRequest<UserProfile[]>('GET', '/users', undefined, accessToken);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Unable to load users.');
      setUsers([]);
      return;
    }
    setUsers(response.data);
  };

  const loadTeams = async () => {
    if (!canAssign || status !== 'authenticated' || !accessToken) return;
    const response = await apiRequest<Team[]>('GET', '/teams', undefined, accessToken);
    if (!response.success || !response.data) {
      setTeams([]);
      return;
    }
    setTeams(response.data);
  };

  useEffect(() => {
    void loadTasks();
    void loadUsers();
    void loadTeams();
    void loadAnalyses();
  }, [status, accessToken, canAssign, isEmployee]);

  const handleCreateTask = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<Task>(
      'POST',
      '/tasks',
      {
        title,
        description,
        assignedTo: assignedTo || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create task.');
      return;
    }

    setTitle('');
    setDescription('');
    setAssignedTo('');
    void loadTasks();
  };

  const sortedTasks = useMemo(() => {
    return [...tasks].sort((a, b) => (a.createdAt ?? '').localeCompare(b.createdAt ?? '')).reverse();
  }, [tasks]);

  const analysisByTaskId = useMemo(() => {
    return analyses.reduce<Record<string, Analysis>>((acc, analysis) => {
      acc[analysis.taskId] = analysis;
      return acc;
    }, {});
  }, [analyses]);

  const userById = useMemo(() => {
    return users.reduce<Record<string, UserProfile>>((acc, userItem) => {
      acc[userItem.id] = userItem;
      return acc;
    }, {});
  }, [users]);

  const handleCreateAnalysis = async (taskId: string) => {
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);
    const response = await apiRequest<Analysis>(
      'POST',
      '/analyses',
      { taskId, analysisType: 'LATENCY' },
      accessToken,
    );
    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create analysis.');
      return;
    }
    void loadAnalyses();
  };

  const handleCreateAssignedAnalysis = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<Analysis>(
      'POST',
      '/analyses/manager',
      {
        title: analysisTitle,
        analysisType,
        assignedTo: analysisAssignedTo,
        teamId: analysisTeamId || null,
        taskDescription: analysisDescription || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create assigned analysis.');
      return;
    }

    setAnalysisTitle('');
    setAnalysisType('LATENCY');
    setAnalysisAssignedTo('');
    setAnalysisTeamId('');
    setAnalysisDescription('');
    void loadTasks();
    void loadAnalyses();
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Tasks</h1>
          <p className="text-sm text-muted">Assigned work and task creation.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to manage tasks.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {canAssign ? (
          <Card>
            <CardHeader>
              <CardTitle>Create task</CardTitle>
              <CardDescription>Assign tasks to employees.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateTask}>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Title</label>
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Description</label>
                  <textarea
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    rows={3}
                    value={description}
                    onChange={(event) => setDescription(event.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Assign to</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={assignedTo}
                    onChange={(event) => setAssignedTo(event.target.value)}
                  >
                    {users.length === 0 ? (
                      <option value="" disabled>
                        No employees available
                      </option>
                    ) : (
                      <option value="">Unassigned</option>
                    )}
                    {users
                      .filter((item) => item.role === 'EMPLOYEE')
                      .map((employee) => (
                        <option key={employee.id} value={employee.id}>
                          {employee.displayName} ({employee.email})
                        </option>
                      ))}
                  </select>
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Creating...' : 'Create task'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        {canAssign ? (
          <Card>
            <CardHeader>
              <CardTitle>Create assigned analysis</CardTitle>
              <CardDescription>Start an analysis and assign ownership.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateAssignedAnalysis}>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Analysis title</label>
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={analysisTitle}
                    onChange={(event) => setAnalysisTitle(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Analysis type</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={analysisType}
                    onChange={(event) => setAnalysisType(event.target.value)}
                  >
                    <option value="LATENCY">Latency</option>
                    <option value="SECURITY">Security</option>
                    <option value="OUTAGE">Outage</option>
                    <option value="CAPACITY">Capacity</option>
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Team</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={analysisTeamId}
                    onChange={(event) => setAnalysisTeamId(event.target.value)}
                  >
                    <option value="">Select team</option>
                    {teams.map((team) => (
                      <option key={team.id} value={team.id}>
                        {team.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Assign to</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={analysisAssignedTo}
                    onChange={(event) => setAnalysisAssignedTo(event.target.value)}
                    required
                  >
                    {users.length === 0 ? (
                      <option value="" disabled>
                        No employees available
                      </option>
                    ) : (
                      <option value="">Select employee</option>
                    )}
                    {users
                      .filter((item) => item.role === 'EMPLOYEE')
                      .map((employee) => (
                        <option key={employee.id} value={employee.id}>
                          {employee.displayName} ({employee.email})
                        </option>
                      ))}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Task description</label>
                  <textarea
                    className="min-h-[90px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={analysisDescription}
                    onChange={(event) => setAnalysisDescription(event.target.value)}
                  />
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Creating...' : 'Create analysis'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Task list</CardTitle>
            <CardDescription>Latest tasks from backend.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {loading ? <p className="text-sm text-muted">Loading tasks...</p> : null}
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            {!loading && !error && sortedTasks.length === 0 ? (
              <p className="text-sm text-muted">No tasks available.</p>
            ) : null}
            <div className="grid gap-3">
              {sortedTasks.map((task) => (
                <Card key={task.id}>
                  <CardHeader>
                    <CardTitle>{task.title}</CardTitle>
                    <CardDescription>{task.description || 'No description'}</CardDescription>
                  </CardHeader>
                  <CardContent className="flex flex-wrap items-center gap-2">
                    <Badge>{task.status}</Badge>
                    {task.assignedTo ? (
                      <span className="text-xs text-muted">
                        Assigned: {userById[task.assignedTo]?.displayName ?? task.assignedTo}
                      </span>
                    ) : null}
                    {isEmployee ? (
                      analysisByTaskId[task.id] ? (
                        <span className="text-xs text-muted">Analysis created</span>
                      ) : (
                        <Button size="sm" onClick={() => handleCreateAnalysis(task.id)} disabled={saving}>
                          Create analysis
                        </Button>
                      )
                    ) : null}
                  </CardContent>
                </Card>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
