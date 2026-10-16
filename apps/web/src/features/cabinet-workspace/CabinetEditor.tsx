import { useQuery } from '@tanstack/react-query';
import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { CabinetDrawing } from '../../entities/cabinet/ui/CabinetDrawing';
import { bodyMaterials, facadeMaterials, configurationSchema, defaultCabinet, type CabinetConfiguration } from '../../entities/cabinet/model/configuration';
import { formatMoney } from '../../shared/lib/formatMoney';
import { useSession } from '../session';
import { workspaceApi, type CabinetProject } from './api';

export function pendingCabinet(): { title: string; configuration: CabinetConfiguration } {
  try {
    const pending = sessionStorage.getItem('furniture-pending-cabinet');
    if (pending) {
      const data: unknown = JSON.parse(pending);
      if (typeof data === 'object' && data !== null && 'configuration' in data && 'title' in data && typeof data.title === 'string') {
        return { title: data.title, configuration: configurationSchema.parse(data.configuration) };
      }
    }
  } catch { sessionStorage.removeItem('furniture-pending-cabinet'); }
  return { title: 'Мой шкаф', configuration: structuredClone(defaultCabinet) };
}

export function CabinetEditor({ project }: { project: CabinetProject | null }) {
  const [initial] = useState(() => project ?? pendingCabinet());
  const [title, setTitle] = useState(initial.title);
  const [history, setHistory] = useState([initial.configuration]);
  const [position, setPosition] = useState(0);
  const config = history[position];
  const [debounced, setDebounced] = useState(config);
  const [step, setStep] = useState(0);
  const [closed, setClosed] = useState(false);
  const [saved, setSaved] = useState(project);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [city, setCity] = useState('');
  const [description, setDescription] = useState('');
  const session = useSession();
  const navigate = useNavigate();
  useEffect(() => { const timer = setTimeout(() => setDebounced(config), 250); return () => clearTimeout(timer); }, [config]);
  const preview = useQuery({ queryKey: ['cabinet-preview', debounced], queryFn: () => workspaceApi.preview(debounced), retry: false });
  const dirty = saved === null || title !== saved.title || JSON.stringify(config) !== JSON.stringify(saved.configuration);
  const waiting = config !== debounced || preview.isPending;
  function change(configuration: CabinetConfiguration) { setHistory([...history.slice(0, position + 1), configuration]); setPosition(position + 1); setMessage(''); setError(''); }
  function remember() { sessionStorage.setItem('furniture-pending-cabinet', JSON.stringify({ title, configuration: config })); }
  async function persist(): Promise<CabinetProject> {
    if (!dirty && saved) return saved;
    const result = await workspaceApi.save(saved, title, config);
    setSaved(result); sessionStorage.removeItem('furniture-pending-cabinet');
    return result;
  }
  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError('');
    try {
      const result = await persist(); setMessage('Проект сохранён');
      if (!project) navigate(`/constructor/${result.id}`, { replace: true });
    } catch (failure) { setError(failure instanceof Error ? failure.message : 'Не удалось сохранить проект.'); }
    finally { setBusy(false); }
  }
  async function publish(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError('');
    try { const result = await workspaceApi.publish(await persist(), city, description); navigate(`/p/${result.token}`); }
    catch (failure) { setError(failure instanceof Error ? failure.message : 'Не удалось опубликовать заявку.'); }
    finally { setBusy(false); }
  }
  async function withdraw() {
    if (!saved) return;
    setBusy(true); setError('');
    try { await workspaceApi.withdraw(saved.id); setMessage('Публикация снята'); }
    catch (failure) { setError(failure instanceof Error ? failure.message : 'Не удалось снять публикацию.'); }
    finally { setBusy(false); }
  }
  const disabled = busy || waiting || preview.isError;
  const steps = ['Размеры', 'Наполнение', 'Материалы', 'Публикация'];
  return <>
    <div className="page-heading"><div><span className="eyebrow">РАСПАШНОЙ ШКАФ</span><h1>Конструктор шкафа</h1><p>Настройте свой вариант и сохраните заявку на изготовление.</p></div><Link className="text-link" to="/projects">Мои проекты →</Link></div>
    <div className="builder-grid">
      <section className="panel drawing-panel" aria-label="Предпросмотр шкафа"><div className="drawing-toolbar"><strong>Ваш шкаф</strong><button type="button" onClick={() => setClosed(!closed)}>{closed ? 'Показать наполнение' : 'Показать фасады'}</button></div><CabinetDrawing configuration={config} closed={closed} /><div className="drawing-toolbar"><button type="button" disabled={position === 0} onClick={() => setPosition(position - 1)}>Отменить</button><button type="button" disabled={position === history.length - 1} onClick={() => setPosition(position + 1)}>Повторить</button></div><div className="builder-estimate">{waiting ? <p role="status">Обновляем расчёт…</p> : preview.isError ? <p className="form-error" role="alert">Расчёт недоступен. Проверьте параметры и соединение.</p> : <><span>Предварительная оценка</span><strong>{formatMoney(preview.data.estimate.costs.total)}</strong>{preview.data.estimate.warnings.map((warning) => <p key={warning}>{warning}</p>)}</>}</div><p className="disclaimer">Демонстрационная оценка prototype-v0. Конструкцию и итоговую цену подтверждает производитель.</p></section>
      <section className="panel settings-panel"><nav className="builder-steps" aria-label="Шаги конструктора">{steps.map((name, index) => <button key={name} type="button" aria-current={step === index ? 'step' : undefined} onClick={() => setStep(index)}>{index + 1}. {name}</button>)}</nav>
        <form onSubmit={(event) => void save(event)}>
          <label>Название проекта<input required minLength={2} maxLength={100} value={title} onChange={(event) => setTitle(event.target.value)} /></label>
          <fieldset hidden={step !== 0}><legend>Габариты в миллиметрах</legend>{([['width', 'Ширина', 300, 5000], ['height', 'Высота', 400, 3000], ['depth', 'Глубина', 200, 1000]] as const).map(([field, label, min, max]) => <label key={field}>{label}, мм<input aria-label={`${label}, мм`} type="number" required min={min} max={max} step={1} value={config[field]} onChange={(event) => change({ ...config, [field]: Number(event.target.value) })} /><small>{min}–{max} мм</small></label>)}</fieldset>
          <fieldset hidden={step !== 1}><legend>Секции и наполнение</legend><p>От 1 до 5 секций одинаковой ширины.</p>{config.sections.map((section, index) => <div className="section-settings" key={`section-${index + 1}`}><h3>Секция {index + 1}</h3><label>Тип секции {index + 1}<select aria-label={`Тип секции ${index + 1}`} value={section.type} onChange={(event) => change({ ...config, sections: config.sections.map((item, at) => at === index ? { ...item, type: event.target.value as typeof section.type } : item) })}><option value="shelves">Полки</option><option value="wardrobe">Штанга и полки</option><option value="drawers">Блок ящиков</option></select></label>{([['shelves', 'Полки', 12], ['drawers', 'Ящики', 6]] as const).map(([field, label, max]) => <label key={field}>{label}, секция {index + 1}<input type="number" min={0} max={max} required step={1} value={section[field]} onChange={(event) => change({ ...config, sections: config.sections.map((item, at) => at === index ? { ...item, [field]: Number(event.target.value) } : item) })} /></label>)}<button type="button" disabled={config.sections.length === 1} onClick={() => change({ ...config, sections: config.sections.filter((_, at) => at !== index) })}>Удалить секцию {index + 1}</button></div>)}<button type="button" className="secondary-action" disabled={config.sections.length === 5} onClick={() => change({ ...config, sections: [...config.sections, { type: 'shelves', shelves: 3, drawers: 0 }] })}>Добавить секцию</button></fieldset>
          <fieldset hidden={step !== 2}><legend>Материалы</legend><label>Корпус<select aria-label="Корпус" value={config.bodyMaterial} onChange={(event) => change({ ...config, bodyMaterial: event.target.value as CabinetConfiguration['bodyMaterial'] })}>{Object.entries(bodyMaterials).map(([value, name]) => <option key={value} value={value}>{name}</option>)}</select></label><label>Фасады<select aria-label="Фасады" value={config.facadeMaterial} onChange={(event) => change({ ...config, facadeMaterial: event.target.value as CabinetConfiguration['facadeMaterial'] })}>{Object.entries(facadeMaterials).map(([value, name]) => <option key={value} value={value}>{name}</option>)}</select></label></fieldset>
          <div className="builder-controls"><button type="button" disabled={step === 0} onClick={() => setStep(step - 1)}>Назад</button><button type="button" disabled={step === 3} onClick={() => setStep(step + 1)}>Далее</button></div>
          {session.data?.user ? <button className="primary-action" disabled={disabled}>{busy ? 'Сохраняем…' : 'Сохранить проект'}</button> : <Link className="secondary-action" to="/login?next=constructor" onClick={remember}>Войти и сохранить шкаф</Link>}
          <p className="save-status" role="status">{dirty ? 'Есть несохранённые изменения' : 'Все изменения сохранены'}</p>
        </form>
        {step === 3 && <form className="publication-form" onSubmit={(event) => void publish(event)}><h2>Заявка на изготовление</h2><p>Размеры, материалы, название и описание будут доступны по ссылке. Контактный email останется закрытым.</p><label>Город<input required minLength={2} maxLength={100} value={city} onChange={(event) => setCity(event.target.value)} /></label><label>Пожелания<textarea maxLength={2000} value={description} onChange={(event) => setDescription(event.target.value)} /></label><label className="check-label"><input type="checkbox" required />Разрешаю публикацию выбранного шкафа по ссылке</label>{session.data?.user ? <button className="primary-action" disabled={disabled}>Опубликовать заявку</button> : <Link to="/register?next=constructor" onClick={remember}>Зарегистрироваться для публикации</Link>}<p className="disclaimer">Ссылкой можно поделиться с производителем. Автоматической отправки пока нет.</p>{saved && <button type="button" disabled={busy} onClick={() => void withdraw()}>Снять публикацию</button>}</form>}
        {message && <p className="success-message" role="status">{message}</p>}{error && <p className="form-error" role="alert">{error}</p>}
      </section>
    </div>
  </>;
}
