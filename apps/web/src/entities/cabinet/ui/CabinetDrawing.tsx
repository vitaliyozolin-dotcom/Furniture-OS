import type { CabinetConfiguration } from '../model/configuration';
const colors = { sonoma: '#c9a77f', cashmere: '#c7bdb0', graphite: '#54565b' };
export function CabinetDrawing({ configuration: config, closed }: { configuration: CabinetConfiguration; closed: boolean }) {
  const width = Math.max(300, config.width);
  const height = Math.max(400, config.height);
  const bay = (width - 36 - (config.sections.length - 1) * 18) / config.sections.length;
  const body = colors[config.bodyMaterial];
  const facade = config.facadeMaterial === 'mirror' ? '#d7e5e8' : config.facadeMaterial === 'mdf' ? '#ede9e1' : body;
  return <svg className="cabinet-drawing" role="img" aria-label={`Шкаф ${config.width} × ${config.height} × ${config.depth} мм`} viewBox={`-100 -100 ${width + 200} ${height + 250}`}>
    <title>Схема шкафа с равными секциями</title>
    <rect x={0} y={0} width={width} height={height} fill={body} stroke="#51483f" strokeWidth={10} />
    {config.sections.map((section, index) => {
      const x = 18 + index * (bay + 18);
      const drawers = Math.min(6, Math.max(0, section.drawers));
      const drawerHeight = Math.min(150, (height - 76) * 0.4 / Math.max(1, drawers));
      const shelfSpace = height - 76 - drawers * drawerHeight;
      return <g key={`section-${index + 1}`} data-section={index + 1}>
        <rect x={x} y={18} width={bay} height={height - 36} fill={closed ? facade : '#ece4d6'} stroke="#65584a" strokeWidth={5} />
        {closed ? <rect data-door x={x + bay - 45} y={height / 2 - 60} width={14} height={120} rx={5} fill="#393937" /> : <>
          {section.type === 'wardrobe' && <line x1={x + 20} x2={x + bay - 20} y1={height * 0.25} y2={height * 0.25} stroke="#8e9396" strokeWidth={14} />}
          {Array.from({ length: Math.min(12, Math.max(0, section.shelves)) }, (_, shelf) => <rect key={`shelf-${shelf + 1}`} x={x} y={18 + shelfSpace * (shelf + 1) / (section.shelves + 1)} width={bay} height={18} fill={body} stroke="#65584a" strokeWidth={3} />)}
          {Array.from({ length: drawers }, (_, drawer) => <g key={`drawer-${drawer + 1}`} data-drawer={drawer + 1}><rect x={x + 8} y={height - 40 - (drawer + 1) * drawerHeight} width={bay - 16} height={drawerHeight - 4} fill={body} stroke="#65584a" strokeWidth={5} /><line x1={x + bay * 0.35} x2={x + bay * 0.65} y1={height - 40 - (drawer + 1) * drawerHeight + drawerHeight / 3} y2={height - 40 - (drawer + 1) * drawerHeight + drawerHeight / 3} stroke="#393937" strokeWidth={10} /></g>)}
        </>}
      </g>;
    })}
    <text x={width / 2} y={height + 95} textAnchor="middle" fontSize={Math.max(36, width / 35)} fill="#51483f">{config.width} × {config.height} × {config.depth} мм</text>
  </svg>;
}
