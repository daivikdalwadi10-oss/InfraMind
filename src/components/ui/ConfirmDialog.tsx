"use client";

import React from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';

type ConfirmDialogProps = {
  trigger: React.ReactNode;
  title: string;
  description?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm: () => void;
  disabled?: boolean;
};

export default function ConfirmDialog({
  trigger,
  title,
  description,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  onConfirm,
  disabled,
}: ConfirmDialogProps) {
  return (
    <Dialog.Root>
      <Dialog.Trigger asChild>{trigger}</Dialog.Trigger>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 bg-black/60" />
        <Dialog.Content className="fixed left-1/2 top-1/2 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/10 bg-slate-950/90 p-6 shadow-xl shadow-black/30 backdrop-blur">
          <div className="flex items-start justify-between">
            <Dialog.Title className="text-lg font-semibold text-white">{title}</Dialog.Title>
            <Dialog.Close className="rounded-full p-1 text-slate-400 hover:text-white">
              <X className="h-4 w-4" />
            </Dialog.Close>
          </div>
          {description && <Dialog.Description className="mt-2 text-sm text-slate-300">{description}</Dialog.Description>}
          <div className="mt-6 flex justify-end gap-3">
            <Dialog.Close
              className="rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/10"
              disabled={disabled}
            >
              {cancelLabel}
            </Dialog.Close>
            <Dialog.Close
              onClick={onConfirm}
              className="rounded-xl bg-blue-500 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-400"
              disabled={disabled}
            >
              {confirmLabel}
            </Dialog.Close>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
