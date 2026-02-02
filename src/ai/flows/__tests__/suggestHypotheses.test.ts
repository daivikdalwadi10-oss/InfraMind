import { describe, it, expect, vi, afterEach } from 'vitest';
import * as genkit from '../../genkit';
import { suggestHypotheses } from '../suggestHypotheses';

afterEach(() => {
  vi.restoreAllMocks();
});

describe('suggestHypotheses', () => {
  it('parses array output and normalizes', async () => {
    vi.spyOn(genkit, 'callGenkit').mockResolvedValue({ success: true, data: { text: '[{"text":"root cause","confidence":85,"evidence":["e1","e2"]}]' } });

    const out = await suggestHypotheses({ taskTitle: 'T', symptoms: ['s1'], signals: ['sig1'] });

    expect(Array.isArray(out)).toBe(true);
    expect(out[0]).toEqual({ text: 'root cause', confidence: 85, evidence: ['e1', 'e2'] });
  });

  it('throws when genkit returns failure', async () => {
    vi.spyOn(genkit, 'callGenkit').mockResolvedValue({ success: false, error: 'genkit error' });
    await expect(suggestHypotheses({ taskTitle: 'T', symptoms: [], signals: [] })).rejects.toThrow(/genkit/i);
  });

  it('throws on parse errors (non-JSON output)', async () => {
    vi.spyOn(genkit, 'callGenkit').mockResolvedValue({ success: true, data: { text: 'Not JSON' } });
    await expect(suggestHypotheses({ taskTitle: 'T', symptoms: [], signals: [] })).rejects.toThrow(/Failed to parse|Failed to parse Genkit output/);
  });
});