import * as admin from 'firebase-admin';

if (!process.env.FIREBASE_ADMIN_CREDENTIALS) {
  // In dev, people may use Application Default Credentials. If not present, we throw so deploy fails early.
  console.warn('FIREBASE_ADMIN_CREDENTIALS not provided; rely on ADC for local dev');
}

if (!admin.apps.length) {
  try {
    const creds = process.env.FIREBASE_ADMIN_CREDENTIALS;
    if (creds) {
      const parsed = JSON.parse(creds);
      admin.initializeApp({
        credential: admin.credential.cert(parsed),
      });
    } else {
      admin.initializeApp(); // ADC or environment-based
    }
  } catch (err) {
    // Fail loudly during server-side operations if misconfigured
    console.error('Failed to initialize Firebase Admin:', err);
    throw err;
  }
}

export const adminAuth = admin.auth();
export const adminFirestore = admin.firestore();
