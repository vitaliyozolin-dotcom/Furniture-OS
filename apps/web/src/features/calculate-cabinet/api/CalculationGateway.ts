import type { CabinetConfig, CalculationResult } from '../../../domain/types';

export interface CalculationGateway {
  calculate(input: CabinetConfig): Promise<CalculationResult>;
}
