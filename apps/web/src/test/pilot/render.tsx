import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react';
import { MemoryRouter, useLocation } from 'react-router-dom';
import type { ReactNode } from 'react';
function Location() { const location = useLocation(); return <output data-testid="location">{location.pathname}{location.search}</output>; }
export function renderPilot(children: ReactNode, path = '/') {
  const cache = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  const result = render(<QueryClientProvider client={cache}><MemoryRouter initialEntries={[path]}>{children}<Location /></MemoryRouter></QueryClientProvider>);
  return { ...result, cache };
}
