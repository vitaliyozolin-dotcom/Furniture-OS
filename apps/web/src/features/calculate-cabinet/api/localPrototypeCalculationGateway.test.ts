import { describe, expect, it } from 'vitest';
import { calculateCabinet } from '../../../domain/calculateCabinet';
import { prototypeCabinetConfig } from '../../../pages/prototypeCabinetConfig';
import { calculationResultSchema } from './calculationResultSchema';
import { localPrototypeCalculationGateway } from './localPrototypeCalculationGateway';

describe('localPrototypeCalculationGateway', () => {
  it('preserves the prototype-v0 calculation behind the gateway', async () => {
    await expect(localPrototypeCalculationGateway.calculate(prototypeCabinetConfig))
      .resolves.toEqual(calculateCabinet(prototypeCabinetConfig));
  });

  it('rejects malformed calculation responses at runtime', () => {
    expect(() => calculationResultSchema.parse({ leadDays: 'ten' })).toThrow();
  });
});
