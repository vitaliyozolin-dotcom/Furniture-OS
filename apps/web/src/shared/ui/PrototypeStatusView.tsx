export type PrototypeStatus = 'ready' | 'loading' | 'error' | 'empty';

const statusCopy = {
  loading: ['Загрузка расчёта', 'Подготавливаем данные прототипа.'],
  error: ['Расчёт недоступен', 'Попробуйте обновить страницу.'],
  empty: ['Нет данных для расчёта', 'Создайте конфигурацию шкафа.'],
} as const;

interface PrototypeStatusViewProps {
  status: Exclude<PrototypeStatus, 'ready'>;
}

export function PrototypeStatusView({ status }: PrototypeStatusViewProps) {
  const [title, description] = statusCopy[status];

  return (
    <main className="app-shell" aria-live="polite">
      <AppHeaderPlaceholder />
      <section className="status-card">
        <h1>{title}</h1>
        <p>{description}</p>
      </section>
    </main>
  );
}

function AppHeaderPlaceholder() {
  return <div className="topbar" aria-hidden="true" />;
}
