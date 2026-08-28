import { describe, expect, it } from 'vitest';
import { calculateCabinet } from './calculateCabinet';
import type { CabinetConfig } from './types';

const standardCabinet: CabinetConfig = {
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

const fixtures: Array<{ name: string; input: CabinetConfig }> = [
  { name: 'standard cabinet shown by the prototype', input: standardCabinet },
  { name: 'shallow cabinet with a wardrobe rod', input: { ...standardCabinet, depth: 450 } },
  { name: 'two sections wider than the current limit', input: { ...standardCabinet, sections: standardCabinet.sections.slice(0, 2) } },
  { name: 'tall cabinet with MDF facades', input: { ...standardCabinet, height: 2700, facadeMaterial: 'mdf' } },
];

describe('prototype-v0 characterization fixtures', () => {
  it.each(fixtures)('$name', ({ input }) => {
    expect(calculateCabinet(input)).toMatchSnapshot();
  });
});
