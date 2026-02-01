import { callGenkit } from '../genkit';

/**
 * Draft an executive summary given task + analysis. Return structured JSON with { summary: string, highlights: string[] }
 */
export async function draftExecutiveSummary({ taskTitle, analysis, _managerNote }: { taskTitle: string; analysis: string; _managerNote?: string }) {
  const prompt = `You are an executive-level SRE summarizer. Given the task: "${taskTitle}", and the full analysis:
${analysis}

Draft a concise executive summary in JSON with keys: summary (string, ~3-6 sentences), highlights (array of 3-6 bullet points), and recommendedAction (short string). Be professional, factual, avoid speculation. Output only valid JSON.`;

  const resp = await callGenkit({ model: 'gemini-2.5-flash', prompt, maxTokens: 500 });
  if (!resp.success) throw new Error(resp.error || 'Genkit failed');

  try {
    const data = resp.data as Record<string, unknown> | undefined;
    let raw: string;
    if (data && typeof data['output'] === 'string') raw = String(data['output']);
    else if (data && typeof data['text'] === 'string') raw = String(data['text']);
    else raw = JSON.stringify(data);

    const parsed = JSON.parse(raw);
    return parsed as { summary: string; highlights: string[]; recommendedAction?: string };
  } catch (err) {
    throw new Error('Failed to parse Genkit output for executive summary: ' + String(err));
  }
}
