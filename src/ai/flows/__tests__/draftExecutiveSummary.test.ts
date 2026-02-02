import { describe, it, expect, vi, afterEach } from 'vitest';
import * as genkit from '../../genkit';
import type { GenkitResponse } from '../../genkit';
import { draftExecutiveSummary } from '../draftExecutiveSummary';

afterEach(() => {
  vi.restoreAllMocks();
});

describe('draftExecutiveSummary', () => {
  it('parses JSON summary output', async () => {
    const mockResp: GenkitResponse = { success: true, data: { text: '{"summary":"Short summary","highlights":["h1","h2"],"recommendedAction":"Do X"}' } };
    vi.spyOn(genkit, 'callGenkit').mockResolvedValue(mockResp as GenkitResponse);

    const out = await draftExecutiveSummary({ taskTitle: 'Task', analysis: 'analysis text' });
    expect(out.summary).toBe('Short summary');
    expect(Array.isArray(out.highlights)).toBe(true);
    expect(out.recommendedAction).toBe('Do X');
  });

  it('throws on parse errors', async () => {
    const mockBad: GenkitResponse = { success: true, data: { text: 'Not JSON' } };
    vi.spyOn(genkit, 'callGenkit').mockResolvedValue(mockBad as GenkitResponse);
    await expect(draftExecutiveSummary({ taskTitle: 'Task', analysis: 'analysis text' })).rejects.toThrow(/Failed to parse/);
  });
});