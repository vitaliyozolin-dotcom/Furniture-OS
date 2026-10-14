import { useQuery } from '@tanstack/react-query';
import type { CabinetConfig } from '../../../domain/types';
import type { CalculationGateway } from '../api/CalculationGateway';
import { migrationCalculationGateway } from '../api/migrationCalculationGateway';

export function usePrototypeCalculation(
  input: CabinetConfig,
  gateway: CalculationGateway = migrationCalculationGateway,
) {
  return useQuery({
    queryKey: ['prototype-calculation', input],
    queryFn: () => gateway.calculate(input),
    staleTime: Number.POSITIVE_INFINITY,
  });
}
