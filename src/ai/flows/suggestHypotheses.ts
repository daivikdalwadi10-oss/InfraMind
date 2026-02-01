import { callGenkit } from '../genkit';

/**
 * Request a structured list of hypotheses. The flow enforces structured output in JSON array:
 * [{ text: string, confidence: number, evidence: string[] }]
 * This must be validated by the caller.
 */
export async function suggestHypotheses({
  taskTitle,
  symptoms,
  signals,
}: {
  taskTitle: string;
  symptoms: string[];
  signals: string[];
}) {
  const prompt = `You are a professional SRE analyst. Given the task title: "${taskTitle}" and the following symptoms: ${JSON.stringify(
    symptoms,
  )} and signals: ${JSON.stringify(signals)}, produce a JSON array called hypotheses. Each hypothesis must have: text (string), confidence (0-100 number), evidence (array of short strings). Only return JSON. No extra commentary.`;

  const resp = await callGenkit({ model: 'gemini-2.5-flash', prompt, maxTokens: 800 });

  if (!resp.success) throw new Error(resp.error || 'Genkit failed');

  // Expecting resp.data to contain text we can parse
  try {
    // Some Genkit responses might include 'output' or 'text'
    const data = resp.data as Record<string, unknown> | undefined;
    let raw: string;
    if (data && typeof data['output'] === 'string') raw = String(data['output']);
    else if (data && typeof data['text'] === 'string') raw = String(data['text']);
    else raw = JSON.stringify(data);

    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) throw new Error('Expected array output');
    // Basic structure validation
    const arr = parsed as Array<Record<string, unknown>>;
    const normalized = arr.map((h) => ({ text: String(h['text']), confidence: Number(h['confidence']), evidence: Array.isArray(h['evidence']) ? (h['evidence'] as unknown[]).map(String) : [] }));
    return normalized;
  } catch (err) {
    throw new Error('Failed to parse Genkit output for hypotheses: ' + String(err));
  }
}
