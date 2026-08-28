import type { CabinetConfig } from '../domain/types';

export const prototypeCabinetConfig: CabinetConfig = {
  width: 2400,
  height: 2400,
  depth: 600,
  sections: [
    { type: 'shelves', shelves: 4, drawers: 0 },
    { type: 'wardrobe', shelves: 1, drawers: 3 },
    { type: 'shelves', shelves: 4, drawers: 0 },
  ],
  bodyMaterial: 'sonoma',
  facadeMaterial: 'chipboard',
  marginPercent: 20,
  laborRate: 800,
};
