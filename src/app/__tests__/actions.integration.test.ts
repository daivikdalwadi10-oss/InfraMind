import { initializeTestEnvironment, assertSucceeds, assertFails } from '@firebase/rules-unit-testing';
import fs from 'fs';
import path from 'path';
import { beforeAll, afterAll, describe, it } from 'vitest';

import type { RulesTestEnvironment } from '@firebase/rules-unit-testing';
let env: RulesTestEnvironment | undefined; 

const hasEmulator = Boolean(process.env.FIRESTORE_EMULATOR_HOST || process.env.FIREBASE_EMULATOR_HOST);
const describeIf = hasEmulator ? describe : describe.skip;

beforeAll(async () => {
  if (!hasEmulator) return;
  const rules = fs.readFileSync(path.resolve(__dirname, '../../../firestore.rules'), 'utf8');
  env = await initializeTestEnvironment({ projectId: 'inframind-test', firestore: { rules } });
});

afterAll(async () => {
  // cleanup test env
  await env?.cleanup();
});

describeIf('integration - Firestore security & flows', () => {
  it('end-to-end flow: manager -> employee -> manager and rules enforcement', async () => {
    const managerUid = 'mgr-1';
    const employeeUid = 'emp-1';
    const ownerUid = 'owner-1';

    // Create user profiles using their own authenticated contexts (rules require request.auth.uid == userId for create)
    const managerAuthDb = env!.authenticatedContext(managerUid, { token: { role: 'manager' } }).firestore();
    const employeeAuthDb = env!.authenticatedContext(employeeUid, { token: { role: 'employee' } }).firestore();
    const ownerAuthDb = env!.authenticatedContext(ownerUid, { token: { role: 'owner' } }).firestore();

    await assertSucceeds(managerAuthDb.collection('users').doc(managerUid).set({ uid: managerUid, displayName: 'M', role: 'manager', createdAt: Date.now(), updatedAt: Date.now(), statusHistory: [] }));
    await assertSucceeds(employeeAuthDb.collection('users').doc(employeeUid).set({ uid: employeeUid, displayName: 'E', role: 'employee', createdAt: Date.now(), updatedAt: Date.now(), statusHistory: [] }));
    await assertSucceeds(ownerAuthDb.collection('users').doc(ownerUid).set({ uid: ownerUid, displayName: 'O', role: 'owner', createdAt: Date.now(), updatedAt: Date.now(), statusHistory: [] }));

    // Manager creates a task (should succeed) using an explicit doc ref so we can capture ID
    const manager = env!.authenticatedContext(managerUid, { token: { role: 'manager' } });
    const managerDb = manager.firestore();
    const taskRef = managerDb.collection('tasks').doc();
    await assertSucceeds(taskRef.set({ title: 'T1', description: 'desc', creator: managerUid, assignee: employeeUid, status: 'ASSIGNED', createdAt: Date.now(), updatedAt: Date.now(), statusHistory: [{ status: 'ASSIGNED', changedAt: Date.now(), changedBy: managerUid }] }));

    // Employee creates analysis as DRAFT (should succeed) and we capture the ID
    const employee = env!.authenticatedContext(employeeUid, { token: { role: 'employee' } });
    const employeeDb = employee.firestore();

    const analysisRef = employeeDb.collection('analyses').doc();
    const analysis = {
      taskId: taskRef.id,
      author: employeeUid,
      symptoms: ['s1','s2','s3'],
      signals: ['sig1','sig2'],
      hypotheses: [{ text: 'h1' }],
      readinessScore: 80,
      status: 'DRAFT',
      createdAt: Date.now(),
      updatedAt: Date.now(),
      statusHistory: [{ status: 'DRAFT', changedAt: Date.now(), changedBy: employeeUid }],
    };

    await assertSucceeds(analysisRef.set(analysis));

    // Employee can update while DRAFT (succeeds)
    await assertSucceeds(analysisRef.set({ ...analysis, signals: ['sig1','sig2','sig3'] }, { merge: true }));

    // Employee can submit (update status to SUBMITTED) because current status is DRAFT (succeeds)
    await assertSucceeds(analysisRef.set({ status: 'SUBMITTED', readinessScore: 80 }, { merge: true }));

    // After submission, employee updates should fail (no longer DRAFT)
    await assertFails(analysisRef.set({ signals: [] }, { merge: true }));

    // Manager can read and approve
    const managerReadDb = env!.authenticatedContext(managerUid, { token: { role: 'manager' } }).firestore();
    const managerAnalysisRef = managerReadDb.collection('analyses').doc(analysisRef.id);
    await assertSucceeds(managerAnalysisRef.get());
    await assertSucceeds(managerAnalysisRef.set({ status: 'APPROVED' }, { merge: true }));

    // Owner cannot read raw analyses
    const ownerDb = env!.authenticatedContext(ownerUid, { token: { role: 'owner' } }).firestore();
    const ownerAnalysisRef = ownerDb.collection('analyses').doc(analysisRef.id);
    await assertFails(ownerAnalysisRef.get());

    // Manager drafts a report (should succeed) and capture its id
    const reportRef = managerReadDb.collection('reports').doc();
    await assertSucceeds(reportRef.set({ taskId: taskRef.id, author: managerUid, status: 'DRAFT', createdAt: Date.now(), updatedAt: Date.now(), statusHistory: [{ status: 'DRAFT', changedAt: Date.now(), changedBy: managerUid }] }));

    // Owner should NOT read draft reports
    await assertFails(ownerDb.collection('reports').doc(reportRef.id).get());

    // Manager finalizes report
    await assertSucceeds(managerReadDb.collection('reports').doc(reportRef.id).set({ status: 'FINALIZED' }, { merge: true }));

    // Owner can read finalized reports
    await assertSucceeds(ownerDb.collection('reports').doc(reportRef.id).get());

    // No explicit cleanup required - test environment will be torn down by env.cleanup()
    // If needed, use admin context or namespaced document IDs to avoid collisions in shared emulators
  });
});