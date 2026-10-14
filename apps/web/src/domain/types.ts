import type { components } from '../shared/api/generated/schema';
export type CabinetConfig = components['schemas']['PrototypeCabinetInputV0'];
export type CalculationResult = components['schemas']['PrototypeCalculationResultV0'];
export type CabinetSection = CabinetConfig['sections'][number];
export type SectionType = CabinetSection['type'];
export type CutPart = CalculationResult['parts'][number];
