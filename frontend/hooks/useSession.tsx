'use client';

import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { apiRequest } from '@/lib/api';
import { clearAccessToken, getAccessToken, setAccessToken } from '@/lib/auth';
import { ApiResponse, UserProfile } from '@/lib/types';

interface SessionContextValue {
  user: UserProfile | null;
  accessToken: string | null;
  status: 'loading' | 'authenticated' | 'unauthenticated' | 'error';
  error?: string;
  refresh: () => Promise<void>;
  setToken: (_token: string) => void;
  signOut: () => void;
}

const SessionContext = createContext<SessionContextValue | undefined>(undefined);

export function SessionProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [accessToken, setAccessTokenState] = useState<string | null>(null);
  const [status, setStatus] = useState<SessionContextValue['status']>('loading');
  const [error, setError] = useState<string | undefined>(undefined);

  const refresh = async () => {
    const token = getAccessToken();
    setAccessTokenState(token);

    if (!token) {
      setUser(null);
      setStatus('unauthenticated');
      setError(undefined);
      return;
    }

    setStatus('loading');
    const response = await apiRequest<{ user: UserProfile }>('GET', '/auth/me', undefined, token);
    if (!response.success || !response.data) {
      setError(response.error || 'Failed to load session');
      setUser(null);
      setStatus('error');
      return;
    }

    setUser(response.data.user);
    setStatus('authenticated');
    setError(undefined);
  };

  useEffect(() => {
    void refresh();
  }, []);

  const setToken = (token: string) => {
    setAccessToken(token);
    setAccessTokenState(token);
    void refresh();
  };

  const signOut = () => {
    clearAccessToken();
    setAccessTokenState(null);
    setUser(null);
    setStatus('unauthenticated');
  };

  const value = useMemo(
    () => ({ user, accessToken, status, error, refresh, setToken, signOut }),
    [user, accessToken, status, error],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession() {
  const ctx = useContext(SessionContext);
  if (!ctx) {
    throw new Error('useSession must be used within SessionProvider');
  }
  return ctx;
}

export async function loginWithEmailPassword(email: string, password: string) {
  const response = await apiRequest<{ accessToken: string; refreshToken: string; user: UserProfile }>(
    'POST',
    '/auth/login',
    { email, password },
  );

  if (!response.success || !response.data) {
    return response as ApiResponse<never>;
  }

  setAccessToken(response.data.accessToken);
  return response;
}
