import { afterEach, describe, expect, it, vi } from 'vitest';
import { createHttpCalculationGateway } from './httpCalculationGateway';
import { prototypeCabinetConfig } from '../../../entities/cabinet/model/prototypeCabinetConfig';
import fixtures from '../../../../../../contracts/fixtures/prototype-v0.json';

afterEach(() => vi.restoreAllMocks());
describe('HTTP calculation boundary', () => {
  it('posts the draft to the API and checks the response', async () => {
    const fetcher = vi.fn<typeof fetch>().mockResolvedValue(Response.json(fixtures[0].expected));
    const result = await createHttpCalculationGateway(fetcher).calculate(prototypeCabinetConfig);
    const request = fetcher.mock.calls[0][0] as Request;
    expect(request.method).toBe('POST');
    expect(request.url).toMatch(/\/api\/prototype\/v0\/calculate$/);
    expect(await request.json()).toEqual(prototypeCabinetConfig);
    expect(request.headers.get('X-Correlation-ID')).toBeTruthy();
    expect(result).toEqual(fixtures[0].expected);
  });
  it('rejects a failed API without a local estimate', async () => {
    const fetcher = vi.fn<typeof fetch>().mockResolvedValue(Response.json({ status: 503 }, { status: 503 }));
    await expect(createHttpCalculationGateway(fetcher).calculate(prototypeCabinetConfig)).rejects.toThrow('503');
  });
  it('rejects malformed successful data', async () => {
    const fetcher = vi.fn<typeof fetch>().mockResolvedValue(Response.json({ costs: { total: 'wrong' } }));
    await expect(createHttpCalculationGateway(fetcher).calculate(prototypeCabinetConfig)).rejects.toThrow();
  });
  it('propagates a network failure', async () => {
    const fetcher = vi.fn<typeof fetch>().mockRejectedValue(new TypeError('offline'));
    await expect(createHttpCalculationGateway(fetcher).calculate(prototypeCabinetConfig)).rejects.toThrow('offline');
  });
});
