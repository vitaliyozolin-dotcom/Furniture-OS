import { render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { AppErrorBoundary } from './AppErrorBoundary';

afterEach(() => vi.restoreAllMocks());

describe('AppErrorBoundary', () => {
  it('replaces a failed render with the explicit error state', () => {
    vi.spyOn(console, 'error').mockImplementation(() => undefined);

    render(
      <AppErrorBoundary>
        <BrokenView />
      </AppErrorBoundary>,
    );

    expect(screen.getByRole('heading', { name: 'Расчёт недоступен' })).toBeInTheDocument();
  });
});

function BrokenView(): never {
  throw new Error('characterized render failure');
}
