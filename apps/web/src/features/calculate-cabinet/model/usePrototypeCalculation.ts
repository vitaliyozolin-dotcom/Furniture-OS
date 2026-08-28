import { useQuery } from '@tanstack/react-query';
import type { CabinetConfig } from '../../../domain/types';
import type { CalculationGateway } from '../api/CalculationGateway';
import { localPrototypeCalculationGateway } from '../api/localPrototypeCalculationGateway';

export function usePrototypeCalculation(
  input: CabinetConfig,
  gateway: CalculationGateway = localPrototypeCalculationGateway,
) {
  return useQuery({
    queryKey: ['prototype-calculation', input],
    queryFn: () => gateway.calculate(input),
    staleTime: Number.POSITIVE_INFINITY,
  });
}
