interface ProjectProgressProps {
  currentStep: number;
}

export function ProjectProgress({ currentStep }: ProjectProgressProps) {
  return (
    <section className="progress-card">
      <div className="progress-head"><span>Готовность проекта</span><strong>{currentStep} из 4</strong></div>
      <div className="progress-line"><i style={{ width: `${currentStep * 25}%` }} /></div>
      <div className="steps">
        <div className="active"><b>1</b><span>Размеры</span></div>
        <div><b>2</b><span>Наполнение</span></div>
        <div><b>3</b><span>Материалы</span></div>
        <div><b>4</b><span>Итог</span></div>
      </div>
    </section>
  );
}
