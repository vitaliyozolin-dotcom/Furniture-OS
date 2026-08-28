import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import Ajv2020 from 'ajv/dist/2020';
import { describe, expect, it } from 'vitest';
import { calculateCabinet } from './calculateCabinet';
import type { CabinetConfig, CalculationResult } from './types';

interface PrototypeFixture {
  id: string;
  description: string;
  input: CabinetConfig;
  expected: CalculationResult;
}

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

const fixtureInputs = [
  { id: 'standard-cabinet', description: 'Standard cabinet shown by the prototype.', input: standardCabinet },
  { id: 'shallow-with-rod', description: 'Shallow cabinet with a wardrobe rod.', input: { ...standardCabinet, depth: 450 } },
  { id: 'wide-sections', description: 'Two sections wider than the current prototype limit.', input: { ...standardCabinet, sections: standardCabinet.sections.slice(0, 2) } },
  { id: 'tall-mdf', description: 'Tall cabinet with MDF facades.', input: { ...standardCabinet, height: 2700, facadeMaterial: 'mdf' as const } },
];

const fixturePath = resolve(process.cwd(), '../../contracts/fixtures/prototype-v0.json');
const inputSchemaPath = resolve(process.cwd(), '../../contracts/prototype-v0/input.schema.json');
const resultSchemaPath = resolve(process.cwd(), '../../contracts/prototype-v0/result.schema.json');

if (process.env.UPDATE_PROTOTYPE_FIXTURES === '1') {
  const generated = fixtureInputs.map(({ id, description, input }) => ({
    id,
    description,
    input,
    expected: calculateCabinet(input),
  }));
  mkdirSync(dirname(fixturePath), { recursive: true });
  writeFileSync(fixturePath, `${JSON.stringify(generated, null, 2)}\n`);
}

const fixtures = JSON.parse(readFileSync(fixturePath, 'utf8')) as PrototypeFixture[];
const ajv = new Ajv2020({ allErrors: true, strict: true });
const validateInput = ajv.compile(JSON.parse(readFileSync(inputSchemaPath, 'utf8')));
const validateResult = ajv.compile(JSON.parse(readFileSync(resultSchemaPath, 'utf8')));

describe('prototype-v0 compatibility fixtures', () => {
  it('keeps fixture identifiers unique', () => {
    expect(new Set(fixtures.map(({ id }) => id)).size).toBe(fixtures.length);
  });

  it.each(fixtures)('$id', ({ input, expected }) => {
    expect(validateInput(input), JSON.stringify(validateInput.errors)).toBe(true);
    expect(validateResult(expected), JSON.stringify(validateResult.errors)).toBe(true);
    expect(calculateCabinet(input)).toEqual(expected);
  });
});
