import type { CabinetConfig, CalculationResult, CutPart } from './types';

const BOARD = 16;
const GAP = 2;
const SHEET_AREA = 2.8 * 2.07;

const bodyPrices = { sonoma: 3650, cashmere: 4100, graphite: 4350 } as const;
const facadePrices = { chipboard: 1850, mdf: 5600, mirror: 7200 } as const;

export function calculateCabinet(config: CabinetConfig): CalculationResult {
  const sectionCount = config.sections.length;
  const innerHeight = config.height - BOARD * 2;
  const innerWidth = config.width - BOARD * 2 - BOARD * (sectionCount - 1);
  const sectionWidth = innerWidth / sectionCount;
  const parts: CutPart[] = [
    { name: 'Боковина', width: config.height, height: config.depth, quantity: 2, material: 'body' },
    { name: 'Крышка', width: config.width - BOARD * 2, height: config.depth, quantity: 1, material: 'body' },
    { name: 'Дно', width: config.width - BOARD * 2, height: config.depth, quantity: 1, material: 'body' },
    { name: 'Задняя стенка', width: config.width - BOARD * 2, height: innerHeight, quantity: 1, material: 'back' },
  ];

  if (sectionCount > 1) {
    parts.push({ name: 'Перегородка', width: innerHeight, height: config.depth, quantity: sectionCount - 1, material: 'body' });
  }

  let runners = 0;
  let rods = 0;
  let shelfCount = 0;

  config.sections.forEach((section, index) => {
    shelfCount += section.shelves;
    runners += section.drawers;
    if (section.type === 'wardrobe' || section.type === 'drawers') rods += 1;

    if (section.shelves > 0) {
      parts.push({ name: `Полка секции ${index + 1}`, width: sectionWidth - 2, height: config.depth - 20, quantity: section.shelves, material: 'body' });
    }

    if (section.drawers > 0) {
      parts.push({ name: `Боковина ящика ${index + 1}`, width: 450, height: 160, quantity: section.drawers * 2, material: 'body' });
      parts.push({ name: `Перед/зад ящика ${index + 1}`, width: sectionWidth - 70, height: 160, quantity: section.drawers * 2, material: 'body' });
      parts.push({ name: `Дно ящика ${index + 1}`, width: sectionWidth - 70, height: 450, quantity: section.drawers, material: 'back' });
    }
  });

  const facadeWidth = (config.width - GAP * (sectionCount + 1)) / sectionCount;
  const facadeHeight = config.height - GAP * 2;
  parts.push({ name: 'Фасад', width: facadeWidth, height: facadeHeight, quantity: sectionCount, material: 'facade' });

  const bodyArea = parts.filter((part) => part.material === 'body').reduce((sum, part) => sum + (part.width * part.height * part.quantity) / 1_000_000, 0);
  const backArea = parts.filter((part) => part.material === 'back').reduce((sum, part) => sum + (part.width * part.height * part.quantity) / 1_000_000, 0);
  const facadeArea = parts.filter((part) => part.material === 'facade').reduce((sum, part) => sum + (part.width * part.height * part.quantity) / 1_000_000, 0);
  const sheets = Math.ceil((bodyArea / SHEET_AREA) * 1.12);
  const hinges = Math.max(3, Math.ceil(config.height / 600)) * sectionCount;
  const edgeMeters = Math.round((bodyArea * 2.7 + shelfCount * sectionWidth * 2 / 1000) * 10) / 10;

  const materials = sheets * bodyPrices[config.bodyMaterial] + backArea * 720 + facadeArea * facadePrices[config.facadeMaterial] + edgeMeters * 38;
  const hardware = hinges * 210 + runners * 1450 + rods * 850 + sectionCount * 480 + 1800;
  const laborHours = 5 + sectionCount * 1.3 + shelfCount * 0.12 + runners * 0.8;
  const labor = laborHours * config.laborRate;
  const delivery = 4500;
  const subtotal = materials + hardware + labor + delivery;
  const margin = subtotal * config.marginPercent / 100;

  const warnings: string[] = [];
  if (sectionWidth > 950) warnings.push(`Секция ${Math.round(sectionWidth)} мм слишком широкая — нужна перегородка.`);
  else if (sectionWidth > 800) warnings.push(`Полки шириной ${Math.round(sectionWidth)} мм требуют усиления.`);
  if (config.depth < 500 && rods > 0) warnings.push('Глубины недостаточно для стандартной продольной штанги.');
  if (config.height > 2600) warnings.push('Высота выше 2600 мм требует проверки транспорта и монтажа.');

  const leadDays = Math.max(5, Math.ceil(4 + sectionCount + runners * 0.7 + (config.facadeMaterial === 'mdf' ? 8 : config.facadeMaterial === 'mirror' ? 4 : 0)));

  return {
    parts,
    sheets,
    hinges,
    runners,
    rods,
    edgeMeters,
    leadDays,
    warnings,
    costs: { materials, hardware, labor, delivery, margin, total: subtotal + margin },
  };
}
