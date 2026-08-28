import { describe, expect, it } from 'vitest';
import { calculateCabinet } from './calculateCabinet';
import type { CabinetConfig } from './types';

const base: CabinetConfig = {
  width: 2400,
  height: 2400,
  depth: 600,
  sections: [
    { type: 'shelves', shelves: 4, drawers: 0 },
    { type: 'wardrobe', shelves: 1, drawers: 3 },
    { type: 'shelves', shelves: 4, drawers: 0 },
  ],
  bodyMaterial: 'sonoma',
  facadeMaterial: 'chipboard',
  marginPercent: 20,
  laborRate: 800,
};

describe('calculateCabinet', () => {
  it('calculates the standard cabinet', () => {
    const result = calculateCabinet(base);
    expect(result.sheets).toBeGreaterThan(0);
    expect(result.hinges).toBe(12);
    expect(result.parts.length).toBeGreaterThan(5);
    expect(result.costs.total).toBeGreaterThan(result.costs.materials);
  });

  it('warns about a shallow cabinet with a rod', () => {
    const result = calculateCabinet({ ...base, depth: 450 });
    expect(result.warnings.some((warning) => warning.includes('Глубины недостаточно'))).toBe(true);
  });

  it('warns about wide sections', () => {
    const result = calculateCabinet({ ...base, sections: base.sections.slice(0, 2) });
    expect(result.warnings.some((warning) => warning.includes('слишком широкая'))).toBe(true);
  });
});
