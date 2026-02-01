import React from 'react';
import { createTaskAction } from '@/src/app/actions';

export default function ManagerPage() {
  // Simple server form using Server Action createTask
  return (
    <div>
      <h1 className="text-2xl font-semibold">Manager Dashboard</h1>

      <section className="mt-6">
        <h2 className="font-medium">Create Task</h2>
        <form action={createTaskAction} className="mt-2 space-y-2 max-w-md">
          <input name="managerUid" placeholder="Your Manager UID" className="w-full border p-2" />
          <input name="title" placeholder="Title" className="w-full border p-2" />
          <textarea name="description" placeholder="Description" className="w-full border p-2" />
          <input name="assignee" placeholder="Assignee UID (optional)" className="w-full border p-2" />
          <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
        </form>
      </section>
    </div>
  );
}
