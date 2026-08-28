import { calculateCabinet } from '../../../domain/calculateCabinet';
import type { CalculationGateway } from './CalculationGateway';
import { calculationResultSchema } from './calculationResultSchema';

export const localPrototypeCalculationGateway: CalculationGateway = {
  async calculate(input) {
    return calculationResultSchema.parse(calculateCabinet(input));
  },
};
