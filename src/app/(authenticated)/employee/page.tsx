import React from 'react';
import { startAnalysisAction } from '@/src/app/actions';

export default function EmployeePage() {
  return (
    <div>
      <h1 className="text-2xl font-semibold">Employee Dashboard</h1>

      <section className="mt-6">
        <h2 className="font-medium">Start Analysis</h2>
        <form action={startAnalysisAction} className="mt-2 space-y-2 max-w-md">
          <input name="employeeUid" placeholder="Your Employee UID" className="w-full border p-2" />
          <input name="taskId" placeholder="Task ID" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded">Start Analysis</button>
        </form>
      </section>
    </div>
  );
}
