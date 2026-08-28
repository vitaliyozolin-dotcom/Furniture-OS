import { EstimateCard } from '../entities/calculation/ui/EstimateCard';
import { CabinetHero } from '../entities/cabinet/ui/CabinetHero';
import { FacadePreviewButton } from '../features/toggle-facades/ui/FacadePreviewButton';
import { usePrototypeCalculation } from '../features/calculate-cabinet/model/usePrototypeCalculation';
import { useDraftStore } from '../features/edit-cabinet/model/draftStore';
import { AppHeader } from '../shared/ui/AppHeader';
import { BottomNavigation } from '../shared/ui/BottomNavigation';
import { ProjectProgress } from '../shared/ui/ProjectProgress';
import { PrototypeStatusView, type PrototypeStatus } from '../shared/ui/PrototypeStatusView';

interface PrototypePageProps {
  status?: PrototypeStatus;
}

export function PrototypePage({ status = 'ready' }: PrototypePageProps) {
  const config = useDraftStore((state) => state.draft);
  const currentStep = useDraftStore((state) => state.currentStep);
  const started = useDraftStore((state) => state.journeyStarted);
  const startJourney = useDraftStore((state) => state.startJourney);
  const calculation = usePrototypeCalculation(config);

  if (status !== 'ready') {
    return <PrototypeStatusView status={status} />;
  }

  if (calculation.isPending) return <PrototypeStatusView status="loading" />;
  if (calculation.isError) return <PrototypeStatusView status="error" />;
  if (!calculation.data) return <PrototypeStatusView status="empty" />;

  const result = calculation.data;

  return (
    <main className="app-shell">
      <AppHeader />
      <CabinetHero
        config={config}
        leadDays={result.leadDays}
        preview={<FacadePreviewButton />}
      />
      <EstimateCard total={result.costs.total} warnings={result.warnings} />
      <ProjectProgress currentStep={currentStep} />
      <button className="primary-action" onClick={startJourney}>
        <span>{started ? 'Переходим к размерам' : 'Продолжить создание'}</span>
        <b>→</b>
      </button>
      <p className="next-step">Следующий шаг · Размеры шкафа</p>
      <BottomNavigation />
    </main>
  );
}
