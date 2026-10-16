import { fireEvent, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { AuthPanel, SessionBar, sessionApi } from './index';
import { renderPilot } from '../../test/pilot/render';
import { account, anonymous, signedIn } from '../../test/pilot/fixtures';
beforeEach(() => { vi.spyOn(sessionApi, 'session').mockResolvedValue(signedIn); vi.spyOn(sessionApi, 'signIn').mockResolvedValue(account); vi.spyOn(sessionApi, 'logout').mockResolvedValue(); sessionStorage.clear(); });
afterEach(() => vi.restoreAllMocks());
it('shows sign-in actions for an anonymous account', async () => {
  vi.mocked(sessionApi.session).mockResolvedValue(anonymous); renderPilot(<SessionBar />);
  expect(await screen.findByRole('link', { name: 'Войти' })).toHaveAttribute('href', '/login'); expect(screen.getByRole('link', { name: 'Регистрация' })).toHaveAttribute('href', '/register');
});
it('logs out, clears private cache and returns home', async () => {
  sessionStorage.setItem('furniture-pending-cabinet', 'private');
  const { cache } = renderPilot(<SessionBar />, '/projects'); cache.setQueryData(['projects', account.id], ['private']);
  await screen.findByRole('button', { name: 'Выйти' }); vi.mocked(sessionApi.session).mockResolvedValue(anonymous);
  fireEvent.click(screen.getByRole('button', { name: 'Выйти' }));
  await waitFor(() => expect(screen.getByTestId('location')).toHaveTextContent(/^\/$/));
  expect(sessionStorage.getItem('furniture-pending-cabinet')).toBeNull(); expect(cache.getQueryData(['projects', account.id])).toBeUndefined();
});
it('shows logout failure and allows another attempt', async () => {
  vi.mocked(sessionApi.logout).mockRejectedValue(new Error('offline')); renderPilot(<SessionBar />);
  fireEvent.click(await screen.findByRole('button', { name: 'Выйти' })); expect(await screen.findByRole('alert')).toHaveTextContent('Не удалось выйти'); expect(screen.getByRole('button', { name: 'Выйти' })).toBeEnabled();
});
it.each(['login', 'register'] as const)('submits %s and routes back to the constructor', async (mode) => {
  const user = userEvent.setup(); renderPilot(<AuthPanel mode={mode} />, `/${mode}?next=constructor`);
  if (mode === 'register') await user.type(screen.getByLabelText('Имя'), 'Мария');
  await user.type(screen.getByLabelText('Email'), 'user@example.test'); await user.type(screen.getByLabelText('Пароль'), 'Password123!');
  await user.click(screen.getByRole('button', { name: mode === 'login' ? 'Войти в аккаунт' : 'Зарегистрироваться' }));
  await waitFor(() => expect(screen.getByTestId('location')).toHaveTextContent('/constructor'));
  expect(sessionApi.signIn).toHaveBeenCalledWith(mode, { name: mode === 'register' ? 'Мария' : '', email: 'user@example.test', password: 'Password123!' });
});
it('routes a regular sign-in to own projects and switches forms', async () => {
  renderPilot(<AuthPanel mode="login" />, '/login');
  expect(screen.getByRole('link', { name: 'Создать новый аккаунт' })).toHaveAttribute('href', '/register');
  fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'user@example.test' } }); fireEvent.change(screen.getByLabelText('Пароль'), { target: { value: 'Password123!' } });
  fireEvent.submit(screen.getByLabelText('Email').closest('form') as HTMLFormElement);
  await waitFor(() => expect(screen.getByTestId('location')).toHaveTextContent('/projects'));
});
it.each([new Error('Неверный пароль'), 'unknown'])('reports failed authentication and restores submit', async (failure) => {
  vi.mocked(sessionApi.signIn).mockRejectedValue(failure); renderPilot(<AuthPanel mode="register" />, '/register?next=constructor');
  expect(screen.getByRole('link', { name: /Уже есть аккаунт/ })).toHaveAttribute('href', '/login?next=constructor');
  fireEvent.submit(screen.getByLabelText('Email').closest('form') as HTMLFormElement);
  expect(await screen.findByRole('alert')).toHaveTextContent(failure instanceof Error ? failure.message : 'Не удалось войти'); expect(screen.getByRole('button', { name: 'Зарегистрироваться' })).toBeEnabled();
});
