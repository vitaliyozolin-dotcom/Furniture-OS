export type SectionType = 'shelves' | 'wardrobe' | 'drawers';

export interface CabinetSection {
  type: SectionType;
  shelves: number;
  drawers: number;
}

export interface CabinetConfig {
  width: number;
  height: number;
  depth: number;
  sections: CabinetSection[];
  bodyMaterial: 'sonoma' | 'cashmere' | 'graphite';
  facadeMaterial: 'chipboard' | 'mdf' | 'mirror';
  marginPercent: number;
  laborRate: number;
}

export interface CutPart {
  name: string;
  width: number;
  height: number;
  quantity: number;
  material: 'body' | 'back' | 'facade';
}

export interface CalculationResult {
  parts: CutPart[];
  sheets: number;
  hinges: number;
  runners: number;
  rods: number;
  edgeMeters: number;
  leadDays: number;
  warnings: string[];
  costs: {
    materials: number;
    hardware: number;
    labor: number;
    delivery: number;
    margin: number;
    total: number;
  };
}
