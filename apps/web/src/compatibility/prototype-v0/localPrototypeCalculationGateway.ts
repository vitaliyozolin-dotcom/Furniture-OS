import { calculateCabinet } from './calculateCabinet';
import type { CalculationGateway } from '../../features/calculate-cabinet/api/CalculationGateway';
import { calculationResultSchema } from '../../features/calculate-cabinet/api/calculationResultSchema';
export const localPrototypeCalculationGateway: CalculationGateway = {
  async calculate(input) { return calculationResultSchema.parse(calculateCabinet(input)); },
};
