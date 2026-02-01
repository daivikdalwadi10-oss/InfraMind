import { adminFirestore } from '../firebase/admin';
import { UserProfile } from './types';

export async function getUserProfile(uid: string): Promise<UserProfile | null> {
  const ref = adminFirestore.collection('users').doc(uid);
  const snap = await ref.get();
  if (!snap.exists) return null;
  return snap.data() as UserProfile;
}

export async function assertHasRole(uid: string, role: 'employee' | 'manager' | 'owner') {
  const profile = await getUserProfile(uid);
  if (!profile) throw new Error('User profile not found');
  if (profile.role !== role) throw new Error(`Requires role ${role}`);
  return profile;
}

export async function assertAtLeastRole(uid: string, roles: Array<'employee' | 'manager' | 'owner'>) {
  const profile = await getUserProfile(uid);
  if (!profile) throw new Error('User profile not found');
  if (!roles.includes(profile.role)) throw new Error(`Requires one of roles: ${roles.join(',')}`);
  return profile;
}
