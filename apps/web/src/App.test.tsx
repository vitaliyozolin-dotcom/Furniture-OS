import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import App from './App';

describe('prototype screen', () => {
  it('renders the characterized estimate, lead time, safety status, and progress', () => {
    render(<App />);

    expect(screen.getByText(/64.302 ₽/)).toBeInTheDocument();
    expect(screen.getByText('10')).toBeInTheDocument();
    expect(screen.getByText('Конструкция проверена')).toBeInTheDocument();
    expect(screen.getByText('1 из 4')).toBeInTheDocument();
  });

  it('toggles the facade preview', async () => {
    const user = userEvent.setup();
    render(<App />);

    const preview = screen.getByRole('button', { name: 'Открыть или закрыть фасады' });
    expect(screen.getByText('Нажмите, чтобы открыть')).toBeInTheDocument();
    expect(preview.querySelector('.cabinet-stage')).not.toHaveClass('is-open');

    await user.click(preview);

    expect(screen.getByText('Закрыть фасады')).toBeInTheDocument();
    expect(preview.querySelector('.cabinet-stage')).toHaveClass('is-open');
  });

  it('preserves the current continue-button transition', async () => {
    const user = userEvent.setup();
    render(<App />);

    await user.click(screen.getByRole('button', { name: /Продолжить создание/ }));

    expect(screen.getByRole('button', { name: /Переходим к размерам/ })).toBeInTheDocument();
  });
});
