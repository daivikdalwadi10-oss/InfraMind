'use client';

import { useEffect, useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { apiRequest } from '@/lib/api';
import { useSession } from '@/hooks/useSession';
import type { Meeting, MeetingStatus } from '@/lib/types';

const statusOptions: MeetingStatus[] = ['SCHEDULED', 'COMPLETED', 'CANCELLED'];

export default function MeetingsPage() {
  const { user, accessToken, status } = useSession();
  const [meetings, setMeetings] = useState<Meeting[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [agenda, setAgenda] = useState('');
  const [meetingStatus, setMeetingStatus] = useState<MeetingStatus>('SCHEDULED');
  const [scheduledAt, setScheduledAt] = useState('');
  const [durationMinutes, setDurationMinutes] = useState(30);
  const [analysisId, setAnalysisId] = useState('');
  const [incidentId, setIncidentId] = useState('');

  const role = user?.role ?? null;
  const canCreate = role === 'MANAGER';

  const loadMeetings = async () => {
    if (status !== 'authenticated' || !accessToken) return;
    setLoading(true);
    setError(null);
    const response = await apiRequest<Meeting[]>('GET', '/meetings', undefined, accessToken);
    if (!response.success || !response.data) {
      setError(response.error || 'Unable to load meetings.');
      setMeetings([]);
      setLoading(false);
      return;
    }
    setMeetings(response.data);
    setLoading(false);
  };

  useEffect(() => {
    void loadMeetings();
  }, [status, accessToken]);

  const handleCreateMeeting = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!accessToken) return;
    setSaving(true);
    setFormError(null);

    const response = await apiRequest<Meeting>(
      'POST',
      '/meetings',
      {
        title,
        agenda: agenda || null,
        status: meetingStatus,
        scheduledAt,
        durationMinutes,
        analysisId: analysisId || null,
        incidentId: incidentId || null,
      },
      accessToken,
    );

    setSaving(false);
    if (!response.success || !response.data) {
      setFormError(response.error || 'Failed to create meeting.');
      return;
    }

    setTitle('');
    setAgenda('');
    setMeetingStatus('SCHEDULED');
    setScheduledAt('');
    setDurationMinutes(30);
    setAnalysisId('');
    setIncidentId('');
    void loadMeetings();
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold text-ink">Meetings</h1>
          <p className="text-sm text-muted">Schedule and track operational meetings.</p>
        </div>

        {status !== 'authenticated' ? (
          <Card>
            <CardHeader>
              <CardTitle>Sign in required</CardTitle>
              <CardDescription>Please sign in to view meetings.</CardDescription>
            </CardHeader>
          </Card>
        ) : null}

        {canCreate ? (
          <Card>
            <CardHeader>
              <CardTitle>Schedule meeting</CardTitle>
              <CardDescription>Set an agenda and time for the next review.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-3" onSubmit={handleCreateMeeting}>
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
                  <label className="text-xs uppercase text-muted">Agenda</label>
                  <textarea
                    className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                    rows={3}
                    value={agenda}
                    onChange={(event) => setAgenda(event.target.value)}
                  />
                </div>
                <div className="grid gap-3 md:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Status</label>
                    <select
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={meetingStatus}
                      onChange={(event) => setMeetingStatus(event.target.value as MeetingStatus)}
                    >
                      {statusOptions.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Scheduled at</label>
                    <input
                      type="datetime-local"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={scheduledAt}
                      onChange={(event) => setScheduledAt(event.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Duration (min)</label>
                    <input
                      type="number"
                      min={15}
                      max={480}
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={durationMinutes}
                      onChange={(event) => setDurationMinutes(Number(event.target.value))}
                    />
                  </div>
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Analysis ID</label>
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={analysisId}
                      onChange={(event) => setAnalysisId(event.target.value)}
                      placeholder="Optional"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs uppercase text-muted">Incident ID</label>
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                      value={incidentId}
                      onChange={(event) => setIncidentId(event.target.value)}
                      placeholder="Optional"
                    />
                  </div>
                </div>
                {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
                <Button type="submit" disabled={saving}>
                  {saving ? 'Scheduling...' : 'Schedule meeting'}
                </Button>
              </form>
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Meeting list</CardTitle>
            <CardDescription>Upcoming and recent sessions.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {loading ? <p className="text-sm text-muted">Loading meetings...</p> : null}
            {error ? <p className="text-sm text-rose-600">{error}</p> : null}
            {!loading && !error && meetings.length === 0 ? (
              <p className="text-sm text-muted">No meetings scheduled.</p>
            ) : null}
            <div className="grid gap-3">
              {meetings.map((meeting) => (
                <Card key={meeting.id}>
                  <CardHeader>
                    <CardTitle>{meeting.title}</CardTitle>
                    <CardDescription>{meeting.agenda || 'No agenda'}</CardDescription>
                  </CardHeader>
                  <CardContent className="flex flex-wrap items-center gap-2">
                    <Badge>{meeting.status}</Badge>
                    <span className="text-xs text-muted">Scheduled: {meeting.scheduledAt}</span>
                    <span className="text-xs text-muted">Duration: {meeting.durationMinutes} min</span>
                    {meeting.analysisId ? (
                      <span className="text-xs text-muted">Analysis: {meeting.analysisId}</span>
                    ) : null}
                    {meeting.incidentId ? (
                      <span className="text-xs text-muted">Incident: {meeting.incidentId}</span>
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
