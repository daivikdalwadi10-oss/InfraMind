"use client";

import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Team, UserProfile } from '@/lib/types';

export default function ManagementPage() {
  const { user, accessToken, status } = useSession();
  const [employees, setEmployees] = useState<UserProfile[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [teamName, setTeamName] = useState('');
  const [teamDescription, setTeamDescription] = useState('');
  const [selectedTeamId, setSelectedTeamId] = useState('');
  const [members, setMembers] = useState<UserProfile[]>([]);
  const [memberToAdd, setMemberToAdd] = useState('');
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const role = user?.role ?? null;
  const canView = role === 'MANAGER' || role === 'OWNER';

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken || !canView) return;

    const load = async () => {
      setLoading(true);
      setError(null);

      const [usersResponse, teamsResponse] = await Promise.all([
        apiRequest<UserProfile[]>('GET', '/users', undefined, accessToken),
        apiRequest<Team[]>('GET', '/teams', undefined, accessToken),
      ]);

      if (usersResponse.success && usersResponse.data) {
        setEmployees(usersResponse.data);
      }
      if (teamsResponse.success && teamsResponse.data) {
        setTeams(teamsResponse.data);
      }

      if (!usersResponse.success || !teamsResponse.success) {
        setError(usersResponse.error || teamsResponse.error || 'Unable to load management data.');
      }

      setLoading(false);
    };

    void load();
  }, [status, accessToken, canView]);

  useEffect(() => {
    if (!selectedTeamId || status !== 'authenticated' || !accessToken || !canView) {
      setMembers([]);
      return;
    }

    const loadMembers = async () => {
      const response = await apiRequest<UserProfile[]>(
        'GET',
        `/teams/${selectedTeamId}/members`,
        undefined,
        accessToken,
      );
      if (response.success && response.data) {
        setMembers(response.data);
      } else {
        setMembers([]);
      }
    };

    void loadMembers();
  }, [selectedTeamId, status, accessToken, canView]);

  const handleCreateTeam = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest<Team>(
      'POST',
      '/teams',
      { name: teamName, description: teamDescription || null },
      accessToken,
    );

    setSaving(false);
    const team = response.data;
    if (!response.success || !team) {
      setError(response.error || 'Failed to create team.');
      return;
    }

    setTeamName('');
    setTeamDescription('');
    setTeams((prev) => [team, ...prev]);
  };

  const handleAddMember = async () => {
    if (!accessToken || !selectedTeamId || !memberToAdd) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest(
      'POST',
      `/teams/${selectedTeamId}/members`,
      { userId: memberToAdd },
      accessToken,
    );

    setSaving(false);
    if (!response.success) {
      setError(response.error || 'Failed to add team member.');
      return;
    }

    setMemberToAdd('');
    const refresh = await apiRequest<UserProfile[]>(
      'GET',
      `/teams/${selectedTeamId}/members`,
      undefined,
      accessToken,
    );
    if (refresh.success && refresh.data) {
      setMembers(refresh.data);
    }
  };

  const handleRemoveMember = async (userId: string) => {
    if (!accessToken || !selectedTeamId) return;
    setSaving(true);
    setError(null);

    const response = await apiRequest(
      'DELETE',
      `/teams/${selectedTeamId}/members/${userId}`,
      undefined,
      accessToken,
    );

    setSaving(false);
    if (!response.success) {
      setError(response.error || 'Failed to remove team member.');
      return;
    }

    setMembers((prev) => prev.filter((member) => member.id !== userId));
  };

  const sortedEmployees = useMemo(() => {
    return [...employees].sort((a, b) => (a.displayName ?? '').localeCompare(b.displayName ?? ''));
  }, [employees]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Manager Control Panel</h1>
          <p className="text-sm text-muted">Team operations, workload, and staffing visibility.</p>
        </div>

        {!canView ? (
          <Card>
            <CardHeader>
              <CardTitle>Access restricted</CardTitle>
              <CardDescription>Only managers and owners can access this panel.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {loading ? <p className="text-sm text-muted">Loading management data...</p> : null}
        {error ? <p className="text-sm text-rose-600">{error}</p> : null}

        {canView ? (
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Teams</CardTitle>
                <CardDescription>Managed teams and ownership.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {role === 'MANAGER' ? (
                  <form className="space-y-2" onSubmit={handleCreateTeam}>
                    <div className="space-y-1">
                      <label className="text-xs uppercase text-muted">Team name</label>
                      <input
                        className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                        value={teamName}
                        onChange={(event) => setTeamName(event.target.value)}
                        required
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs uppercase text-muted">Description</label>
                      <input
                        className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                        value={teamDescription}
                        onChange={(event) => setTeamDescription(event.target.value)}
                      />
                    </div>
                    <Button type="submit" disabled={saving}>
                      {saving ? 'Creating...' : 'Create team'}
                    </Button>
                  </form>
                ) : null}
                {teams.length === 0 ? <p className="text-sm text-muted">No teams found.</p> : null}
                <div className="space-y-2">
                  {teams.map((team) => (
                    <div key={team.id} className="rounded-lg border border-slate-200 p-3">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium">{team.name}</p>
                        <Badge>{team.id.slice(0, 6)}</Badge>
                      </div>
                      {team.description ? <p className="text-xs text-muted mt-2">{team.description}</p> : null}
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Employees</CardTitle>
                <CardDescription>Active staffing and workload visibility.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {sortedEmployees.length === 0 ? (
                  <p className="text-sm text-muted">No employees available.</p>
                ) : (
                  <div className="space-y-2">
                    {sortedEmployees.map((employee) => (
                      <div key={employee.id} className="rounded-lg border border-slate-200 p-3">
                        <div className="flex items-center justify-between">
                          <div>
                            <p className="text-sm font-medium text-ink">{employee.displayName}</p>
                            <p className="text-xs text-muted">{employee.email}</p>
                          </div>
                          <Badge>{employee.role}</Badge>
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted">
                          {employee.position ? <span>Position: {employee.position}</span> : null}
                          {employee.teams ? <span>Teams: {employee.teams}</span> : null}
                          {typeof employee.active_analysis_count === 'number' ? (
                            <span>Active analyses: {employee.active_analysis_count}</span>
                          ) : null}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        ) : null}

        {canView ? (
          <Card>
            <CardHeader>
              <CardTitle>Team membership</CardTitle>
              <CardDescription>Assign or remove employees from teams.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="grid gap-3 md:grid-cols-3">
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Select team</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={selectedTeamId}
                    onChange={(event) => setSelectedTeamId(event.target.value)}
                  >
                    <option value="">Choose team</option>
                    {teams.map((team) => (
                      <option key={team.id} value={team.id}>
                        {team.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-xs uppercase text-muted">Add member</label>
                  <select
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    value={memberToAdd}
                    onChange={(event) => setMemberToAdd(event.target.value)}
                    disabled={!selectedTeamId}
                  >
                    <option value="">Select employee</option>
                    {employees
                      .filter((employee) => employee.role === 'EMPLOYEE')
                      .map((employee) => (
                        <option key={employee.id} value={employee.id}>
                          {employee.displayName} ({employee.email})
                        </option>
                      ))}
                  </select>
                </div>
                <div className="flex items-end">
                  <Button onClick={handleAddMember} disabled={!selectedTeamId || !memberToAdd || saving}>
                    Add member
                  </Button>
                </div>
              </div>

              {selectedTeamId ? (
                <div className="space-y-2">
                  {members.length === 0 ? <p className="text-sm text-muted">No members yet.</p> : null}
                  {members.map((member) => (
                    <div key={member.id} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                      <div>
                        <p className="text-sm font-medium text-ink">{member.displayName}</p>
                        <p className="text-xs text-muted">{member.email}</p>
                      </div>
                      {role === 'MANAGER' ? (
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleRemoveMember(member.id)}
                          disabled={saving}
                        >
                          Remove
                        </Button>
                      ) : null}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted">Select a team to manage members.</p>
              )}
            </CardContent>
          </Card>
        ) : null}

        {canView ? (
          <Card>
            <CardHeader>
              <CardTitle>Actions</CardTitle>
              <CardDescription>Team membership adjustments happen in the Teams screen.</CardDescription>
            </CardHeader>
            <CardContent>
              <Button asChild variant="secondary">
                <a href="/tasks">Create assignments</a>
              </Button>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </AppShell>
  );
}
