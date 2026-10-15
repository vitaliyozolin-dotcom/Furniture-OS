import { render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import App from './App';
import fixtures from '../../../../contracts/fixtures/prototype-v0.json';
afterEach(() => vi.restoreAllMocks());
it('boots providers and routing with the HTTP calculation', async () => {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async () => Response.json(fixtures[0].expected));
  render(<App />);
  expect(await screen.findByText(/64.302 ₽/)).toBeInTheDocument();
});
