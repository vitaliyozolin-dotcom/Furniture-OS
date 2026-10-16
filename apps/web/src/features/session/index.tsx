import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { sessionApi } from './api';
export { sessionApi } from './api';
export function useSession() { return useQuery({ queryKey: ['session'], queryFn: sessionApi.session, retry: false }); }

export function SessionBar() {
  const session = useSession();
  const cache = useQueryClient();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  async function logout() {
    setBusy(true); setError('');
    try {
      await sessionApi.logout();
      sessionStorage.removeItem('furniture-pending-cabinet');
      cache.removeQueries({ predicate: (query) => query.queryKey[0] !== 'session' });
      await cache.invalidateQueries({ queryKey: ['session'] });
      navigate('/');
    } catch { setError('Не удалось выйти. Попробуйте ещё раз.'); }
    finally { setBusy(false); }
  }
  return <div className="session-bar" aria-label="Аккаунт">
    {session.data?.user ? <><Link to="/projects">{session.data.user.name}</Link><button type="button" disabled={busy} onClick={() => void logout()}>Выйти</button></> : <><Link to="/login">Войти</Link><Link className="session-register" to="/register">Регистрация</Link></>}
    {error && <span role="alert">{error}</span>}
  </div>;
}

export function AuthPanel({ mode }: { mode: 'login' | 'register' }) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const cache = useQueryClient();
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const next = params.get('next') === 'constructor' ? '/constructor' : '/projects';
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError('');
    try {
      await sessionApi.signIn(mode, { name, email, password });
      await cache.invalidateQueries({ queryKey: ['session'] });
      navigate(next);
    } catch (failure) { setError(failure instanceof Error ? failure.message : 'Не удалось войти.'); }
    finally { setBusy(false); }
  }
  return <section className="panel auth-panel">
    <span className="eyebrow">ЛИЧНЫЕ ПРОЕКТЫ</span>
    <h1>{mode === 'register' ? 'Создать аккаунт' : 'С возвращением'}</h1>
    <p>Сохраняйте шкафы и делитесь заявкой на изготовление.</p>
    <form onSubmit={(event) => void submit(event)}>
      {mode === 'register' && <label>Имя<input autoComplete="name" required minLength={2} maxLength={80} value={name} onChange={(event) => setName(event.target.value)} /></label>}
      <label>Email<input type="email" autoComplete="email" required maxLength={254} value={email} onChange={(event) => setEmail(event.target.value)} /></label>
      <label>Пароль<input type="password" autoComplete={mode === 'register' ? 'new-password' : 'current-password'} required minLength={mode === 'register' ? 10 : 1} maxLength={128} value={password} onChange={(event) => setPassword(event.target.value)} /></label>
      {mode === 'register' && <small>Не менее 10 символов. Не используйте пароль от других сервисов.</small>}
      {error && <p className="form-error" role="alert">{error}</p>}
      <button className="primary-action" disabled={busy}>{busy ? 'Подождите…' : mode === 'register' ? 'Зарегистрироваться' : 'Войти в аккаунт'}</button>
    </form>
    <Link to={`${mode === 'register' ? '/login' : '/register'}${params.get('next') === 'constructor' ? '?next=constructor' : ''}`}>{mode === 'register' ? 'Уже есть аккаунт? Войти' : 'Создать новый аккаунт'}</Link>
  </section>;
}
