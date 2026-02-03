import * as admin from 'firebase-admin';

const useEmulators = process.env.FIRESTORE_EMULATOR_HOST || process.env.FIREBASE_AUTH_EMULATOR_HOST;

if (!process.env.FIREBASE_ADMIN_CREDENTIALS && !useEmulators) {
  console.warn('FIREBASE_ADMIN_CREDENTIALS not provided; rely on ADC for local dev');
}

if (!admin.apps.length) {
  try {
    if (useEmulators) {
      // When using emulators, initialize with minimal config - no credentials needed
      admin.initializeApp({
        projectId: 'demo-project',
      });
      // eslint-disable-next-line no-console
      console.log('Firebase Admin initialized for emulators');
    } else {
      const creds = process.env.FIREBASE_ADMIN_CREDENTIALS;
      if (creds) {
        const parsed = JSON.parse(creds);
        admin.initializeApp({
          credential: admin.credential.cert(parsed),
        });
      } else {
        admin.initializeApp(); // ADC or environment-based
      }
    }
  } catch (err) {
    console.error('Failed to initialize Firebase Admin:', err);
    throw err;
  }
}

export const adminAuth = admin.auth();
export const adminFirestore = admin.firestore();
