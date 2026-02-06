'use client';

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Task } from '@/lib/types';

type HealthResponse = { status: string; timestamp: string };

export default function DashboardPage() {
  const { user, accessToken, status } = useSession();
  const [health, setHealth] = useState<HealthResponse | null>(null);
  const [healthError, setHealthError] = useState<string | null>(null);
  const [healthLoading, setHealthLoading] = useState(false);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [tasksError, setTasksError] = useState<string | null>(null);
  const [tasksLoading, setTasksLoading] = useState(false);

  const role = user?.role ?? null;
  const isOwner = role === 'OWNER';
  const isManager = role === 'MANAGER';

  useEffect(() => {
    const loadHealth = async () => {
      setHealthLoading(true);
      setHealthError(null);
      const response = await apiRequest<HealthResponse>('GET', '/health');
      if (!response.success || !response.data) {
        setHealthError(response.error || 'Unable to reach backend.');
        setHealth(null);
        setHealthLoading(false);
        return;
      }
      setHealth(response.data);
      setHealthLoading(false);
    };

    void loadHealth();
  }, []);

  useEffect(() => {
    if (status !== 'authenticated' || !accessToken || isOwner) {
      return;
    }

    const loadTasks = async () => {
      setTasksLoading(true);
      setTasksError(null);
      const response = await apiRequest<Task[]>('GET', '/tasks', undefined, accessToken);
      if (!response.success || !response.data) {
        setTasksError(response.error || 'Unable to load tasks.');
        setTasks([]);
        setTasksLoading(false);
        return;
      }
      setTasks(response.data);
      setTasksLoading(false);
    };

    void loadTasks();
  }, [status, accessToken, isOwner]);

  const taskSummary = useMemo(() => {
    if (!tasks.length) return null;
    const open = tasks.filter((task) => task.status === 'OPEN').length;
    const inProgress = tasks.filter((task) => task.status === 'IN_PROGRESS').length;
    const completed = tasks.filter((task) => task.status === 'COMPLETED').length;
    return { open, inProgress, completed };
  }, [tasks]);

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Dashboard</h1>
          <p className="text-sm text-muted">Operational workspace health and assignments.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view your dashboard.</CardDescription>
            </CardHeader>
            <CardContent>
              <Button asChild>
                <Link href="/login">Go to login</Link>
              </Button>
            </CardContent>
          </Card>
        ) : null}

        <div className="grid gap-4 md:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Backend status</CardTitle>
              <CardDescription>Connectivity check</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
              {healthLoading ? <p className="text-sm text-muted">Checking...</p> : null}
              {healthError ? <p className="text-sm text-rose-600">{healthError}</p> : null}
              {health ? (
                <div className="flex items-center gap-2">
                  <Badge variant="success">{health.status}</Badge>
                  <span className="text-xs text-muted">{new Date(health.timestamp).toLocaleString()}</span>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Role</CardTitle>
              <CardDescription>Access scope</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-semibold text-ink">{role ?? 'Unknown'}</p>
              <p className="text-xs text-muted">Role-aware navigation enabled.</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tasks snapshot</CardTitle>
              <CardDescription>Latest assignments</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
              {isOwner ? <p className="text-sm text-muted">Owners do not manage tasks.</p> : null}
              {!isOwner && tasksLoading ? <p className="text-sm text-muted">Loading tasks...</p> : null}
              {!isOwner && tasksError ? <p className="text-sm text-rose-600">{tasksError}</p> : null}
              {!isOwner && taskSummary ? (
                <div className="flex flex-wrap gap-2 text-xs">
                  <Badge variant="info">Open: {taskSummary.open}</Badge>
                  <Badge variant="warning">In progress: {taskSummary.inProgress}</Badge>
                  <Badge variant="success">Completed: {taskSummary.completed}</Badge>
                </div>
              ) : null}
              {!isOwner && !tasksLoading && !tasksError && !taskSummary ? (
                <p className="text-sm text-muted">No tasks available.</p>
              ) : null}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Next step</CardTitle>
            <CardDescription>Continue your workflow.</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            <Button asChild>
              <Link href="/tasks">View tasks</Link>
            </Button>
            {!isOwner ? (
              <Button asChild variant="secondary">
                <Link href="/analysis">View analyses</Link>
              </Button>
            ) : null}
            {isManager ? (
              <Button asChild variant="secondary">
                <Link href="/reviews">Review queue</Link>
              </Button>
            ) : null}
            <Button asChild variant="secondary">
              <Link href="/reports">Reports</Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
