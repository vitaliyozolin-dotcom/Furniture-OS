import { afterEach, expect, it, vi } from 'vitest';
import { migrationCalculationGateway } from './migrationCalculationGateway';
import { prototypeCabinetConfig } from '../../../entities/cabinet/model/prototypeCabinetConfig';
import fixtures from '../../../../../../contracts/fixtures/prototype-v0.json';
afterEach(() => { vi.unstubAllEnvs(); vi.restoreAllMocks(); });
it('uses the API by default', async () => {
  vi.stubEnv('VITE_ENABLE_PROTOTYPE_V0_FALLBACK', 'false');
  const fetcher = vi.spyOn(globalThis, 'fetch').mockImplementation(async () => Response.json(fixtures[0].expected));
  expect(await migrationCalculationGateway.calculate(prototypeCabinetConfig)).toEqual(fixtures[0].expected);
  expect(fetcher).toHaveBeenCalledOnce();
});
it('retains an explicitly enabled rollback until signed parity', async () => {
  vi.stubEnv('VITE_ENABLE_PROTOTYPE_V0_FALLBACK', 'true');
  const fetcher = vi.spyOn(globalThis, 'fetch');
  expect(await migrationCalculationGateway.calculate(prototypeCabinetConfig)).toEqual(fixtures[0].expected);
  expect(fetcher).not.toHaveBeenCalled();
});
