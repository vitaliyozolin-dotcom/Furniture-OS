import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PrototypeStatusView } from './PrototypeStatusView';

describe('PrototypeStatusView', () => {
  it.each([
    ['loading', 'Загрузка расчёта'],
    ['error', 'Расчёт недоступен'],
    ['empty', 'Нет данных для расчёта'],
  ] as const)('renders the %s state', (status, title) => {
    render(<PrototypeStatusView status={status} />);

    expect(screen.getByRole('heading', { name: title })).toBeInTheDocument();
  });
});
