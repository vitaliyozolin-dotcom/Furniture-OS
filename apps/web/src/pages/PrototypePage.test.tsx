import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import fixtures from '../../../../contracts/fixtures/prototype-v0.json';
import { PrototypePage } from './PrototypePage';
import { renderWithProviders } from '../test/renderWithProviders';
import { useDraftStore } from '../features/edit-cabinet/model/draftStore';

beforeEach(() => { vi.spyOn(globalThis, 'fetch').mockImplementation(async () => Response.json(fixtures[0].expected)); });
afterEach(() => { useDraftStore.getState().reset(); vi.restoreAllMocks(); });

describe('prototype screen', () => {
  it('shows a network error without a local price', async () => {
    vi.mocked(globalThis.fetch).mockRejectedValue(new TypeError('offline'));
    renderWithProviders(<PrototypePage />);
    expect(await screen.findByText('Расчёт недоступен')).toBeInTheDocument();
    expect(screen.queryByText(/64.302 ₽/)).not.toBeInTheDocument();
  });
  it('shows the loading state while waiting for the API', () => {
    vi.mocked(globalThis.fetch).mockImplementation(() => new Promise(() => {}));
    renderWithProviders(<PrototypePage />);
    expect(screen.getByText('Загрузка расчёта')).toBeInTheDocument();
  });
  it('renders the characterized estimate, lead time, safety status, and progress', async () => {
    renderWithProviders(<PrototypePage />);

    expect(await screen.findByText(/64.302 ₽/)).toBeInTheDocument();
    expect(screen.getByText('10')).toBeInTheDocument();
    expect(screen.getByText('Конструкция проверена')).toBeInTheDocument();
    expect(screen.getByText('1 из 4')).toBeInTheDocument();
  });

  it('toggles the facade preview', async () => {
    const user = userEvent.setup();
    renderWithProviders(<PrototypePage />);

    await screen.findByText(/64.302 ₽/);
    const preview = screen.getByRole('button', { name: 'Открыть или закрыть фасады' });
    expect(screen.getByText('Нажмите, чтобы открыть')).toBeInTheDocument();
    expect(preview.querySelector('.cabinet-stage')).not.toHaveClass('is-open');

    await user.click(preview);

    expect(screen.getByText('Закрыть фасады')).toBeInTheDocument();
    expect(preview.querySelector('.cabinet-stage')).toHaveClass('is-open');
  });

  it('preserves the current continue-button transition', async () => {
    const user = userEvent.setup();
    renderWithProviders(<PrototypePage />);

    await screen.findByText(/64.302 ₽/);
    const continueLink = screen.getByRole('link', { name: /Продолжить создание/ });
    expect(continueLink).toHaveAttribute('href', '/constructor');
    continueLink.addEventListener('click', (event) => event.preventDefault());
    await user.click(continueLink);

    expect(screen.getByRole('link', { name: /Переходим к размерам/ })).toBeInTheDocument();
  });
});
