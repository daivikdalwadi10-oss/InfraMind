import { describe, it, expect, vi, afterEach } from 'vitest';

// Mocks must be declared before importing the module under test
vi.mock('@/src/lib/auth', () => ({ assertHasRole: vi.fn(async () => ({})), assertAtLeastRole: vi.fn(async () => ({})) }));
vi.mock('firebase-admin', () => ({ default: { firestore: { FieldValue: { arrayUnion: (v: unknown) => v } } } }));

// We'll create per-test adminFirestore behaviors
const mockCollection = vi.fn();
vi.mock('@/src/firebase/admin', () => ({ adminFirestore: { collection: mockCollection } }));

// Mock AI flow used by managerReviewAnalysis
vi.mock('@/src/ai/flows/draftExecutiveSummary', () => ({ draftExecutiveSummary: vi.fn(async () => ({ summary: 'Executive summary', highlights: [] })) }));

import * as actions from '../actions';

afterEach(() => {
  vi.restoreAllMocks();
});

describe('Server Actions', () => {
  it('submitAnalysis -> succeeds when readiness >= 75 and updates doc', async () => {
    const employeeUid = 'u-emp';
    const analysisId = 'a-1';

    const fakeAnalysis = {
      id: analysisId,
      author: employeeUid,
      status: 'DRAFT',
      symptoms: ['s1', 's2', 's3'],
      signals: ['sig1', 'sig2'],
      hypotheses: [{ text: 'h1' }, { text: 'h2' }],
    };

    const updateMock = vi.fn();
    mockCollection.mockImplementation((name: string) => {
      if (name === 'analyses') {
        return {
          doc: (_id: string) => ({ get: async () => ({ exists: true, data: () => fakeAnalysis }), update: updateMock }),
        }; 
      }
      return { doc: () => ({ get: async () => ({ exists: false }) }) };
    });

    await expect(actions.submitAnalysis(analysisId)).resolves.toEqual({ success: true });
    expect(updateMock).toHaveBeenCalled();
    const calledWith = updateMock.mock.calls[0][0];
    expect(calledWith.status).toBe('SUBMITTED');
    expect(typeof calledWith.readinessScore).toBe('number');
    expect(calledWith.readinessScore).toBeGreaterThanOrEqual(75);
    // Expect statusHistory entry to have changedBy set to employeeUid
    expect(calledWith.statusHistory).toBeDefined();
    // statusHistory may be provided directly (mocked arrayUnion returns the value) or as an array
    const sh = calledWith.statusHistory;
    if (Array.isArray(sh)) {
      expect(sh[0].status).toBe('SUBMITTED');
      expect(sh[0].changedBy).toBe(employeeUid);
      expect(typeof sh[0].changedAt).toBe('number');
    } else {
      expect(sh.status).toBe('SUBMITTED');
      expect(sh.changedBy).toBe(employeeUid);
      expect(typeof sh.changedAt).toBe('number');
    }
  });


  it('submitAnalysis -> throws when readiness < 75', async () => {
    const employeeUid = 'u-emp';
    const analysisId = 'a-2';

    const fakeAnalysis = {
      id: analysisId,
      author: employeeUid,
      status: 'DRAFT',
      symptoms: [],
      signals: [],
      hypotheses: [],
    };

    mockCollection.mockImplementation((name: string) => {
      if (name === 'analyses') {
        return { doc: (_id: string) => ({ get: async () => ({ exists: true, data: () => fakeAnalysis }), update: vi.fn() }) };
      }
      return { doc: () => ({ get: async () => ({ exists: false }) }) };
    });

    await expect(actions.submitAnalysis(analysisId)).rejects.toThrow(/Readiness score must be/i);
  });

  it('managerReviewAnalysis -> APPROVE creates draft report with executive summary', async () => {
    const managerUid = 'u-mgr';
    const analysisId = 'a-3';

    const fakeAnalysis = {
      id: analysisId,
      author: 'u-emp',
      status: 'SUBMITTED',
      taskId: 't-1',
    };

    const addMock = vi.fn(async (_doc: Record<string, unknown>) => ({ id: 'r-1' }));
    const analysisUpdateMock = vi.fn();

    mockCollection.mockImplementation((name: string) => {
      if (name === 'analyses') {
        return { doc: (_id: string) => ({ get: async () => ({ exists: true, data: () => fakeAnalysis }), update: analysisUpdateMock }) };
      }
      if (name === 'tasks') {
        return { doc: (_id: string) => ({ get: async () => ({ exists: true, data: () => ({ title: 'Task Title' }) }) }) };
      }
      if (name === 'reports') {
        return { add: addMock };
      }
      return { doc: () => ({ get: async () => ({ exists: false }) }) };
    });

    const res = await actions.managerReviewAnalysis(analysisId, { type: 'APPROVE' });
    expect(res?.reportId).toBe('r-1');
    expect(addMock).toHaveBeenCalled();
    // analysis update should have been called to set APPROVED
    expect(analysisUpdateMock).toHaveBeenCalled();
    const analysisUpdateArg = analysisUpdateMock.mock.calls[0][0];
    expect(analysisUpdateArg.status).toBe('APPROVED');
    const ash = analysisUpdateArg.statusHistory;
    if (Array.isArray(ash)) {
      expect(ash[0].status).toBe('APPROVED');
      expect(ash[0].changedBy).toBe(managerUid);
    } else {
      expect(ash.status).toBe('APPROVED');
      expect(ash.changedBy).toBe(managerUid);
    }

    const created = addMock.mock.calls[0][0] as Record<string, unknown>;
    const execDraft = created.executiveSummaryDraft as Record<string, unknown> | undefined;
    expect(execDraft?.text).toBe('Executive summary');
    expect(created.status).toBe('DRAFT');
    // created report should have initial statusHistory entry
    const ch = created.statusHistory as Array<Record<string, unknown>>;
    expect(Array.isArray(ch)).toBe(true);
    expect(ch[0].changedBy).toBe(managerUid);
  });

  it('managerReviewAnalysis -> NEEDS_CHANGES updates status', async () => {
    const managerUid = 'u-mgr';
    const analysisId = 'a-4';

    const fakeAnalysis = { id: analysisId, status: 'SUBMITTED' };
    const analysisUpdateMock = vi.fn();

    mockCollection.mockImplementation((name: string) => {
      if (name === 'analyses') {
        return { doc: (_id: string) => ({ get: async () => ({ exists: true, data: () => fakeAnalysis }), update: analysisUpdateMock }) };
      }
      return { doc: () => ({ get: async () => ({ exists: false }) }) };
    });

    const res = await actions.managerReviewAnalysis(analysisId, { type: 'NEEDS_CHANGES', feedback: 'Fix this' });
    expect(res?.success).toBe(true);
    expect(analysisUpdateMock).toHaveBeenCalled();
    const updateArg = analysisUpdateMock.mock.calls[0][0];
    expect(updateArg.status).toBe('NEEDS_CHANGES');
    // statusHistory entry should include managerUid and note
    const nsh = updateArg.statusHistory;
    if (Array.isArray(nsh)) {
      expect(nsh[0].status).toBe('NEEDS_CHANGES');
      expect(nsh[0].changedBy).toBe(managerUid);
      expect(nsh[0].note).toBe('Fix this');
    } else {
      expect(nsh.status).toBe('NEEDS_CHANGES');
      expect(nsh.changedBy).toBe(managerUid);
      expect(nsh.note).toBe('Fix this');
    }
  });
});
