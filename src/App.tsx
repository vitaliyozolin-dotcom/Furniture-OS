import { useMemo, useState } from 'react';
import { calculateCabinet } from './domain/calculateCabinet';
import type { CabinetConfig, CabinetSection } from './domain/types';
import './styles.css';

const initialSections: CabinetSection[] = [
  { type: 'shelves', shelves: 4, drawers: 0 },
  { type: 'wardrobe', shelves: 1, drawers: 3 },
  { type: 'shelves', shelves: 4, drawers: 0 },
];

const money = (value: number) => `${Math.round(value).toLocaleString('ru-RU')} ₽`;

function CabinetPreview({ open }: { open: boolean }) {
  return (
    <div className={`cabinet-stage ${open ? 'is-open' : ''}`}>
      <div className="cabinet-shadow" />
      <div className="cabinet-shell">
        <div className="cabinet-inside">
          <div className="bay bay-left"><i/><i/><i/><i/></div>
          <div className="bay bay-center"><span className="rail"/><b/><b/><b/></div>
          <div className="bay bay-right"><i/><i/><i/><i/></div>
        </div>
        <div className="door door-left"><span/></div>
        <div className="door door-center"><span/></div>
        <div className="door door-right"><span/></div>
      </div>
    </div>
  );
}

export default function App() {
  const [config] = useState<CabinetConfig>({
    width: 2400,
    height: 2400,
    depth: 600,
    sections: initialSections,
    bodyMaterial: 'sonoma',
    facadeMaterial: 'chipboard',
    marginPercent: 20,
    laborRate: 800,
  });
  const [open, setOpen] = useState(false);
  const [started, setStarted] = useState(false);
  const result = useMemo(() => calculateCabinet(config), [config]);

  return (
    <main className="app-shell">
      <header className="topbar">
        <button className="icon-button" aria-label="Меню">☰</button>
        <div className="brand"><strong>Furniture OS</strong><span>Новый проект</span></div>
        <button className="avatar" aria-label="Профиль">В</button>
      </header>

      <section className="hero-card">
        <div className="hero-copy">
          <span className="eyebrow">РАСПАШНОЙ ШКАФ</span>
          <h1>Создайте шкаф<br/>за 3 минуты</h1>
          <p>Стоимость, материалы, фурнитура и КП считаются автоматически.</p>
        </div>

        <button className="preview-button" onClick={() => setOpen((value) => !value)} aria-label="Открыть или закрыть фасады">
          <CabinetPreview open={open} />
          <span className="preview-hint">{open ? 'Закрыть фасады' : 'Нажмите, чтобы открыть'}</span>
        </button>

        <div className="product-meta">
          <div><span>Габариты</span><strong>{config.width} × {config.height} × {config.depth}</strong><small>мм</small></div>
          <div><span>Срок</span><strong>{result.leadDays}</strong><small>рабочих дней</small></div>
        </div>
      </section>

      <section className="price-card">
        <div>
          <span>Ориентировочная стоимость</span>
          <strong>{money(result.costs.total)}</strong>
        </div>
        <div className={`safety ${result.warnings.length ? 'warning' : ''}`}>
          <i />
          <span>{result.warnings[0] ?? 'Конструкция проверена'}</span>
        </div>
      </section>

      <section className="progress-card">
        <div className="progress-head"><span>Готовность проекта</span><strong>1 из 4</strong></div>
        <div className="progress-line"><i /></div>
        <div className="steps">
          <div className="active"><b>1</b><span>Размеры</span></div>
          <div><b>2</b><span>Наполнение</span></div>
          <div><b>3</b><span>Материалы</span></div>
          <div><b>4</b><span>Итог</span></div>
        </div>
      </section>

      <button className="primary-action" onClick={() => setStarted(true)}>
        <span>{started ? 'Переходим к размерам' : 'Продолжить создание'}</span>
        <b>→</b>
      </button>
      <p className="next-step">Следующий шаг · Размеры шкафа</p>

      <nav className="bottom-nav" aria-label="Основная навигация">
        <button><span>⌂</span><small>Проекты</small></button>
        <button className="create"><span>＋</span><small>Новый шкаф</small></button>
        <button><span>◯</span><small>Кабинет</small></button>
      </nav>
    </main>
  );
}
