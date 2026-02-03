import { cookies } from 'next/headers';
import { Role, UserProfile } from './types';

const SESSION_COOKIE = 'inframind_session';
const SESSION_TTL_MS = 14 * 24 * 60 * 60 * 1000;

export interface SessionUser {
  uid: string;
  email: string;
  name: string;
  role: Role;
}

export async function getUserProfile(_uid: string): Promise<UserProfile | null> {
  // This would be called with token from session
  // For now, profile is fetched on login and stored in session
  return null;
}

export async function assertHasRole(_uid: string, role: Role) {
  const user = await getSessionUser();
  if (!user) throw new Error('Authentication required');
  if (user.role !== role) throw new Error(`Requires role ${role}`);
  return user;
}

export async function assertAtLeastRole(_uid: string, roles: Role[]) {
  const user = await getSessionUser();
  if (!user) throw new Error('Authentication required');
  if (!roles.includes(user.role)) throw new Error(`Requires one of roles: ${roles.join(',')}`);
  return user;
}

export async function getSessionUser(): Promise<SessionUser | null> {
  const cookieStore = await cookies();
  const cookie = cookieStore.get(SESSION_COOKIE)?.value;
  if (!cookie) return null;
  try {
    const session = JSON.parse(Buffer.from(cookie, 'base64').toString('utf-8')) as SessionUser;
    return session;
  } catch {
    // Session invalid - clear cookie
    await clearSessionCookie();
    return null;
  }
}

export async function requireSessionUser() {
  const user = await getSessionUser();
  if (!user) throw new Error('Authentication required');
  return user;
}

export async function createSessionCookie(user: SessionUser) {
  const expiresIn = SESSION_TTL_MS;
  const cookie = Buffer.from(JSON.stringify(user)).toString('base64');
  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE, cookie, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: Math.floor(expiresIn / 1000),
    path: '/',
  });
}

export async function clearSessionCookie() {
  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE, '', { httpOnly: true, maxAge: 0, path: '/' });
}
