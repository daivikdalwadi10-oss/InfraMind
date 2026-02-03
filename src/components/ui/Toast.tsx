"use client";

import React from 'react';
import * as ToastPrimitive from '@radix-ui/react-toast';
import { CheckCircle2, XCircle } from 'lucide-react';

type ToastPayload = {
  title: string;
  description?: string;
  variant?: 'success' | 'error';
};

type ToastContextValue = {
  show: (_payload: ToastPayload) => void;
};

const ToastContext = React.createContext<ToastContextValue | null>(null);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = React.useState(false);
  const [payload, setPayload] = React.useState<ToastPayload>({ title: '' });

  const show = React.useCallback((next: ToastPayload) => {
    setPayload(next);
    setOpen(false);
    window.setTimeout(() => setOpen(true), 30);
  }, []);

  return (
    <ToastContext.Provider value={{ show }}>
      <ToastPrimitive.Provider swipeDirection="right">
        {children}
        <ToastPrimitive.Root
          open={open}
          onOpenChange={setOpen}
          className="pointer-events-auto w-[320px] rounded-2xl border border-white/10 bg-slate-950/90 p-4 shadow-xl shadow-black/40 backdrop-blur"
        >
          <div className="flex items-start gap-3">
            <div className="mt-1">
              {payload.variant === 'error' ? (
                <XCircle className="h-5 w-5 text-rose-400" />
              ) : (
                <CheckCircle2 className="h-5 w-5 text-emerald-400" />
              )}
            </div>
            <div>
              <ToastPrimitive.Title className="text-sm font-semibold text-white">
                {payload.title}
              </ToastPrimitive.Title>
              {payload.description && (
                <ToastPrimitive.Description className="mt-1 text-xs text-slate-300">
                  {payload.description}
                </ToastPrimitive.Description>
              )}
            </div>
          </div>
        </ToastPrimitive.Root>
        <ToastPrimitive.Viewport className="fixed bottom-6 right-6 z-50 flex flex-col gap-3 outline-none" />
      </ToastPrimitive.Provider>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = React.useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within ToastProvider');
  }
  return context;
}
