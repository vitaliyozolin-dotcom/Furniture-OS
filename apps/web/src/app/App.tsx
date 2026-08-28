import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { PrototypePage } from '../pages/PrototypePage';
import { AppErrorBoundary } from './AppErrorBoundary';
import { AppProviders } from './AppProviders';

export default function App() {
  return (
    <AppErrorBoundary>
      <AppProviders>
        <BrowserRouter basename={import.meta.env.BASE_URL}>
          <Routes>
            <Route path="*" element={<PrototypePage />} />
          </Routes>
        </BrowserRouter>
      </AppProviders>
    </AppErrorBoundary>
  );
}
