import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { AppHeader } from '../shared/ui/AppHeader';
import { BottomNavigation } from '../shared/ui/BottomNavigation';
import { formatMoney } from '../shared/lib/formatMoney';
import { AuthPanel, useSession } from '../features/session';
import { CabinetEditor } from '../features/cabinet-workspace/CabinetEditor';
import { workspaceApi } from '../features/cabinet-workspace/api';
import { CabinetDrawing } from '../entities/cabinet/ui/CabinetDrawing';
import { bodyMaterials, facadeMaterials } from '../entities/cabinet/model/configuration';

export function PilotShell({ children }: { children: React.ReactNode }) {
  return <main className="app-shell pilot-shell"><AppHeader />{children}<BottomNavigation /></main>;
}
export function AuthPage({ mode }: { mode: 'login' | 'register' }) { return <PilotShell><AuthPanel key={mode} mode={mode} /></PilotShell>; }

export function ConstructorPage() {
  const { id } = useParams();
  const session = useSession();
  const project = useQuery({ queryKey: ['project', session.data?.user?.id, id], queryFn: () => workspaceApi.project(id as string), enabled: Boolean(id && session.data?.user), retry: false });
  if (id && session.isPending) return <PilotShell><p role="status">Открываем проект…</p></PilotShell>;
  if (id && !session.data?.user) return <PilotShell><h1>Войдите, чтобы открыть проект</h1><Link to="/login">Войти</Link></PilotShell>;
  if (id && project.isPending) return <PilotShell><p role="status">Загружаем шкаф…</p></PilotShell>;
  if (id && project.isError) return <PilotShell><h1>Проект недоступен</h1><p role="alert">{project.error.message}</p><Link to="/projects">К моим проектам</Link></PilotShell>;
  return <PilotShell><CabinetEditor key={id ?? 'new'} project={project.data ?? null} /></PilotShell>;
}

export function ProjectsPage() {
  const session = useSession();
  const projects = useQuery({ queryKey: ['projects', session.data?.user?.id], queryFn: workspaceApi.projects, enabled: Boolean(session.data?.user), retry: false });
  return <PilotShell><div className="page-heading"><div><span className="eyebrow">ЛИЧНЫЙ КАБИНЕТ</span><h1>Мои шкафы</h1></div><Link className="secondary-action" to="/constructor">Новый шкаф</Link></div>
    {session.isPending ? <p role="status">Проверяем вход…</p> : !session.data?.user ? <section className="panel"><h2>Сохраняйте свои проекты</h2><p>Войдите или создайте аккаунт, чтобы открыть сохранённые шкафы.</p><Link to="/login">Войти</Link> · <Link to="/register">Регистрация</Link></section> : projects.isPending ? <p role="status">Загружаем проекты…</p> : projects.isError ? <p role="alert">{projects.error.message}</p> : <div className="project-list">{projects.data.length === 0 && <section className="panel"><h2>Здесь будет ваш первый шкаф</h2><p>Начните с размеров и выберите наполнение.</p><Link to="/constructor">Создать шкаф</Link></section>}{projects.data.map((project) => <article className="panel project-card" key={project.id}><div><h2>{project.title}</h2><p>{project.configuration.width} × {project.configuration.height} × {project.configuration.depth} мм · {project.configuration.sections.length} секции</p><small>Версия {project.version}</small></div><Link className="secondary-action" to={`/constructor/${project.id}`}>Открыть проект</Link></article>)}</div>}
  </PilotShell>;
}

export function PublicationPage() {
  const { token = '' } = useParams();
  const publication = useQuery({ queryKey: ['publication', token], queryFn: () => workspaceApi.publication(token), retry: false });
  if (publication.isPending) return <PilotShell><p role="status">Открываем заявку…</p></PilotShell>;
  if (publication.isError) return <PilotShell><h1>Заявка недоступна</h1><p role="alert">{publication.error.message}</p><Link to="/constructor">Создать свой шкаф</Link></PilotShell>;
  const item = publication.data;
  return <PilotShell><div className="page-heading"><div><span className="eyebrow">ЗАЯВКА НА ИЗГОТОВЛЕНИЕ</span><h1>{item.title}</h1><p>{item.city}</p></div><Link className="secondary-action" to="/constructor">Создать свой шкаф</Link></div><div className="builder-grid"><section className="panel"><CabinetDrawing configuration={item.configuration} closed={false} /></section><section className="panel publication-details"><h2>Желаемый шкаф</h2><p>{item.configuration.width} × {item.configuration.height} × {item.configuration.depth} мм</p><p>Корпус: {bodyMaterials[item.configuration.bodyMaterial]}</p><p>Фасады: {facadeMaterials[item.configuration.facadeMaterial]}</p><p className="publication-description">{item.description}</p><span>Предварительная оценка</span><strong className="publication-price">{formatMoney(item.estimate.costs.total)}</strong><p className="disclaimer">Концепция для обсуждения с производителем. Конструкцию и окончательную цену необходимо подтвердить.</p><label>Ссылка на заявку<input readOnly value={`${window.location.origin}/p/${item.token}`} onFocus={(event) => event.currentTarget.select()} /></label><p>Поделитесь ссылкой с выбранным производителем. Личные данные автора здесь не публикуются.</p></section></div></PilotShell>;
}
