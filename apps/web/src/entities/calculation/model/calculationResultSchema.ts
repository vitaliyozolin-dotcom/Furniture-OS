import { z } from 'zod';

const cutPartSchema = z.object({
  name: z.string(),
  width: z.number().finite(),
  height: z.number().finite(),
  quantity: z.number().finite(),
  material: z.enum(['body', 'back', 'facade']),
});

export const calculationResultSchema = z.object({
  parts: z.array(cutPartSchema),
  sheets: z.number().finite(),
  hinges: z.number().finite(),
  runners: z.number().finite(),
  rods: z.number().finite(),
  edgeMeters: z.number().finite(),
  leadDays: z.number().finite(),
  warnings: z.array(z.string()),
  costs: z.object({
    materials: z.number().finite(),
    hardware: z.number().finite(),
    labor: z.number().finite(),
    delivery: z.number().finite(),
    margin: z.number().finite(),
    total: z.number().finite(),
  }),
});
