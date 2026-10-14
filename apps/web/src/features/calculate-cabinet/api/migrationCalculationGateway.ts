import type { CalculationGateway } from './CalculationGateway';
import { httpCalculationGateway } from './httpCalculationGateway';

// Temporary rollback only. Remove after signed parity and owner acceptance.
export const migrationCalculationGateway: CalculationGateway = {
  async calculate(input) {
    if (import.meta.env.VITE_ENABLE_PROTOTYPE_V0_FALLBACK === 'true') {
      const { localPrototypeCalculationGateway } = await import('../../../compatibility/prototype-v0/localPrototypeCalculationGateway');
      return localPrototypeCalculationGateway.calculate(input);
    }
    return httpCalculationGateway.calculate(input);
  },
};
