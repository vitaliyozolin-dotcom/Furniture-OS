import type { ReactNode } from 'react';
import type { CabinetConfig } from '../../../domain/types';

interface CabinetHeroProps {
  config: CabinetConfig;
  leadDays: number;
  preview: ReactNode;
}

export function CabinetHero({ config, leadDays, preview }: CabinetHeroProps) {
  return (
    <section className="hero-card">
      <div className="hero-copy">
        <span className="eyebrow">РАСПАШНОЙ ШКАФ</span>
        <h1>Создайте шкаф<br/>за 3 минуты</h1>
        <p>Стоимость, материалы, фурнитура и КП считаются автоматически.</p>
      </div>
      {preview}
      <div className="product-meta">
        <div><span>Габариты</span><strong>{config.width} × {config.height} × {config.depth}</strong><small>мм</small></div>
        <div><span>Срок</span><strong>{leadDays}</strong><small>рабочих дней</small></div>
      </div>
    </section>
  );
}
