import { useMemo, useState } from 'react';
import { calculateCabinet } from './domain/calculateCabinet';
import type { CabinetConfig, CabinetSection } from './domain/types';
import './styles.css';

const defaultSections: CabinetSection[] = [
  { type: 'shelves', shelves: 4, drawers: 0 },
  { type: 'wardrobe', shelves: 1, drawers: 3 },
  { type: 'shelves', shelves: 4, drawers: 0 },
];

const money = (value: number) => `${Math.round(value).toLocaleString('ru-RU')} ₽`;

export default function App() {
  const [config, setConfig] = useState<CabinetConfig>({
    width: 2400,
    height: 2400,
    depth: 600,
    sections: defaultSections,
    bodyMaterial: 'sonoma',
    facadeMaterial: 'chipboard',
    marginPercent: 20,
    laborRate: 800,
  });
  const [selectedSection, setSelectedSection] = useState(0);
  const result = useMemo(() => calculateCabinet(config), [config]);

  const updateDimension = (key: 'width' | 'height' | 'depth', value: number) => {
    setConfig((current) => ({ ...current, [key]: value }));
  };

  const setSectionCount = (count: number) => {
    setConfig((current) => {
      const sections = Array.from({ length: count }, (_, index) => current.sections[index] ?? { type: 'shelves', shelves: 4, drawers: 0 });
      return { ...current, sections };
    });
    setSelectedSection((index) => Math.min(index, count - 1));
  };

  const updateSection = (patch: Partial<CabinetSection>) => {
    setConfig((current) => ({
      ...current,
      sections: current.sections.map((section, index) => index === selectedSection ? { ...section, ...patch } : section),
    }));
  };

  return (
    <main className="app-shell">
      <header className="topbar">
        <div><strong>FURNITURE OS</strong><span>Cabinet Engine 0.1</span></div>
        <button onClick={() => localStorage.setItem('furniture-os-project', JSON.stringify(config))}>Сохранить</button>
      </header>

      <section className="workspace">
        <aside className="panel controls">
          <h2>Параметры шкафа</h2>
          <div className="dimension-grid">
            {(['width', 'height', 'depth'] as const).map((key) => (
              <label key={key}>{key === 'width' ? 'Ширина' : key === 'height' ? 'Высота' : 'Глубина'}
                <input type="number" value={config[key]} onChange={(event) => updateDimension(key, Number(event.target.value))} />
              </label>
            ))}
          </div>

          <h3>Секции</h3>
          <div className="segment-row">{[1,2,3,4].map((count) => <button className={config.sections.length === count ? 'active' : ''} key={count} onClick={() => setSectionCount(count)}>{count}</button>)}</div>

          <div className="section-tabs">{config.sections.map((_, index) => <button className={selectedSection === index ? 'active' : ''} key={index} onClick={() => setSelectedSection(index)}>Секция {index + 1}</button>)}</div>
          <div className="section-editor">
            <select value={config.sections[selectedSection].type} onChange={(event) => updateSection({ type: event.target.value as CabinetSection['type'] })}>
              <option value="shelves">Полки</option><option value="wardrobe">Штанга</option><option value="drawers">Ящики</option>
            </select>
            <label>Полки<input type="number" min="0" max="8" value={config.sections[selectedSection].shelves} onChange={(event) => updateSection({ shelves: Number(event.target.value) })} /></label>
            <label>Ящики<input type="number" min="0" max="6" value={config.sections[selectedSection].drawers} onChange={(event) => updateSection({ drawers: Number(event.target.value) })} /></label>
          </div>

          <h3>Материалы</h3>
          <select value={config.bodyMaterial} onChange={(event) => setConfig({ ...config, bodyMaterial: event.target.value as CabinetConfig['bodyMaterial'] })}><option value="sonoma">Дуб Сонома</option><option value="cashmere">Кашемир</option><option value="graphite">Графит</option></select>
          <select value={config.facadeMaterial} onChange={(event) => setConfig({ ...config, facadeMaterial: event.target.value as CabinetConfig['facadeMaterial'] })}><option value="chipboard">ЛДСП</option><option value="mdf">МДФ</option><option value="mirror">Зеркало</option></select>
        </aside>

        <section className="panel preview">
          <div className="cabinet" style={{ aspectRatio: `${config.width}/${config.height}`, gridTemplateColumns: `repeat(${config.sections.length},1fr)` }}>
            {config.sections.map((section, index) => <div className="cabinet-section" key={index}><div className="section-label">{index + 1}</div>{Array.from({length: section.shelves}).map((_, shelf) => <span className="shelf" key={shelf}/>)}</div>)}
          </div>
          <div className="size-label">{config.width} × {config.height} × {config.depth} мм</div>
        </section>

        <aside className="panel summary">
          <span className="eyebrow">Цена клиенту</span><div className="total">{money(result.costs.total)}</div>
          <div className={`status ${result.warnings.length ? 'warning' : ''}`}>{result.warnings[0] ?? 'Базовая инженерная проверка пройдена.'}</div>
          {Object.entries(result.costs).filter(([key]) => key !== 'total').map(([key,value]) => <div className="summary-row" key={key}><span>{key}</span><b>{money(value)}</b></div>)}
          <h3>Спецификация</h3>
          <div className="summary-row"><span>Листы ЛДСП</span><b>{result.sheets}</b></div>
          <div className="summary-row"><span>Петли</span><b>{result.hinges}</b></div>
          <div className="summary-row"><span>Направляющие</span><b>{result.runners}</b></div>
          <div className="summary-row"><span>Срок</span><b>{result.leadDays} дн.</b></div>
        </aside>
      </section>
    </main>
  );
}
