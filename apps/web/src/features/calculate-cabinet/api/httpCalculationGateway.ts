import createClient from 'openapi-fetch';
import type { paths } from '../../../shared/api/generated/schema';
import type { CalculationGateway } from './CalculationGateway';
import { calculationResultSchema } from './calculationResultSchema';

export function createHttpCalculationGateway(fetcher: typeof fetch = globalThis.fetch): CalculationGateway {
  const client = createClient<paths>({ baseUrl: globalThis.location?.origin ?? 'http://localhost', fetch: fetcher });
  return {
    async calculate(input) {
      const { data, error, response } = await client.POST('/api/prototype/v0/calculate', {
        body: input,
        signal: AbortSignal.timeout(15_000),
        headers: { 'X-Correlation-ID': crypto.randomUUID() },
      });
      if (!response.ok || error) throw new Error(`Calculation unavailable (${response.status})`);
      return calculationResultSchema.parse(data);
    },
  };
}
export const httpCalculationGateway: CalculationGateway = {
  calculate: (input) => createHttpCalculationGateway().calculate(input),
};
