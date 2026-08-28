import { formatMoney } from '../../../shared/lib/formatMoney';

interface EstimateCardProps {
  total: number;
  warnings: string[];
}

export function EstimateCard({ total, warnings }: EstimateCardProps) {
  return (
    <section className="price-card">
      <div>
        <span>Ориентировочная стоимость</span>
        <strong>{formatMoney(total)}</strong>
      </div>
      <div className={`safety ${warnings.length ? 'warning' : ''}`}>
        <i />
        <span>{warnings[0] ?? 'Конструкция проверена'}</span>
      </div>
    </section>
  );
}
