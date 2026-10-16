import { z } from 'zod';
import type { components } from '../../../shared/api/generated/schema';
export type CabinetConfiguration = components['schemas']['CabinetConfiguration'];
export const configurationSchema = z.object({
  width: z.number().int().min(300).max(5000), height: z.number().int().min(400).max(3000), depth: z.number().int().min(200).max(1000),
  sections: z.array(z.object({ type: z.enum(['shelves', 'wardrobe', 'drawers']), shelves: z.number().int().min(0).max(12), drawers: z.number().int().min(0).max(6) })).min(1).max(5),
  bodyMaterial: z.enum(['sonoma', 'cashmere', 'graphite']), facadeMaterial: z.enum(['chipboard', 'mdf', 'mirror']),
}) satisfies z.ZodType<CabinetConfiguration>;
export const defaultCabinet: CabinetConfiguration = { width: 2400, height: 2400, depth: 600, sections: [{ type: 'shelves', shelves: 4, drawers: 0 }, { type: 'wardrobe', shelves: 1, drawers: 2 }, { type: 'shelves', shelves: 4, drawers: 0 }], bodyMaterial: 'sonoma', facadeMaterial: 'chipboard' };
export const bodyMaterials = { sonoma: 'Дуб сонома', cashmere: 'Кашемир', graphite: 'Графит' };
export const facadeMaterials = { chipboard: 'ЛДСП', mdf: 'МДФ', mirror: 'Зеркало' };
