const GENKIT_API_KEY = process.env.GENKIT_API_KEY;
const GENKIT_BASE = 'https://api.genkit.example/v1'; // placeholder for Genkit endpoint

if (!GENKIT_API_KEY) {
  console.warn('GENKIT_API_KEY not set. AI calls will fail until provided.');
}

export type GenkitResponse<T = unknown> = {
  success: boolean;
  data?: T;
  error?: string;
};

export async function callGenkit<T = unknown>(payload: { model: string; prompt: string; maxTokens?: number }): Promise<GenkitResponse<T>> {
  if (!GENKIT_API_KEY) return { success: false, error: 'GENKIT_API_KEY missing' };

  // Resolve a fetch implementation: prefer global fetch, fall back to node-fetch dynamically
  const globalFetch = (globalThis as unknown as { fetch?: typeof fetch }).fetch;
  const fetchFn: typeof fetch = globalFetch ?? (await import('node-fetch').then((m) => (m.default ?? m) as unknown as typeof fetch));

  const res = await fetchFn(`${GENKIT_BASE}/generate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${GENKIT_API_KEY}`,
    },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const text = await res.text();
    return { success: false, error: `Genkit error: ${res.status} ${text}` };
  }

  const data = await res.json();

  return { success: true, data };
}
