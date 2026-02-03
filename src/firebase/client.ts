import { initializeApp, getApps, getApp } from 'firebase/app';
import { getAuth, connectAuthEmulator } from 'firebase/auth';
import { getFirestore, connectFirestoreEmulator } from 'firebase/firestore';

const useEmulators = process.env.NEXT_PUBLIC_USE_EMULATORS === 'true';

const envOr = (value: string | undefined, fallback: string) =>
  value && value.trim().length > 0 ? value : fallback;

const firebaseConfig = {
  apiKey: envOr(
    process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
    'AIzaSyD000000000000000000000000000000'
  ),
  authDomain: envOr(process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN, 'localhost'),
  projectId: envOr(process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID, 'demo-project'),
  storageBucket: envOr(process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET, 'demo-bucket'),
  messagingSenderId: envOr(
    process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
    '000000000000'
  ),
  appId: envOr(process.env.NEXT_PUBLIC_FIREBASE_APP_ID, 'demo-app-id'),
};

if (!useEmulators) {
  const missingKeys = Object.entries({
    apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
    authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
    projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
    storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
    appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID,
  })
    .filter(([, value]) => !value)
    .map(([key]) => key);

  if (missingKeys.length) {
    throw new Error(
      `Missing Firebase client env vars: ${missingKeys.join(', ')}. ` +
        'Set them in .env.local and restart the dev server.'
    );
  }
}

function initClient() {
  if (!getApps().length) {
    initializeApp(firebaseConfig);
  } else {
    getApp();
  }
}

initClient();

export const auth = getAuth();
export const db = getFirestore();

if (useEmulators) {
  const authHost = process.env.NEXT_PUBLIC_FIREBASE_AUTH_EMULATOR_HOST ?? '127.0.0.1:9099';
  const [authHostname, authPort] = authHost.split(':');
  connectAuthEmulator(auth, `http://${authHostname}:${authPort}`, { disableWarnings: true });

  const firestoreHost = process.env.NEXT_PUBLIC_FIRESTORE_EMULATOR_HOST ?? '127.0.0.1:8081';
  const [firestoreHostname, firestorePort] = firestoreHost.split(':');
  connectFirestoreEmulator(db, firestoreHostname, Number(firestorePort));
  
  // eslint-disable-next-line no-console
  console.log('Firebase emulators connected:', {
    auth: `http://${authHostname}:${authPort}`,
    firestore: `${firestoreHostname}:${firestorePort}`
  });
}
