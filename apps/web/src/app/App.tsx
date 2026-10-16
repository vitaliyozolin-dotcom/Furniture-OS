import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { PrototypePage } from '../pages/PrototypePage';
import { AuthPage, ConstructorPage, ProjectsPage, PublicationPage } from '../pages/CabinetPilotPages';
import { SessionBar } from '../features/session';
import { AppErrorBoundary } from './AppErrorBoundary';
import { AppProviders } from './AppProviders';

export default function App() {
  return (
    <AppErrorBoundary>
      <AppProviders>
        <BrowserRouter basename={import.meta.env.BASE_URL}>
          <SessionBar />
          <Routes>
            <Route path="/login" element={<AuthPage mode="login" />} />
            <Route path="/register" element={<AuthPage mode="register" />} />
            <Route path="/constructor" element={<ConstructorPage />} />
            <Route path="/constructor/:id" element={<ConstructorPage />} />
            <Route path="/projects" element={<ProjectsPage />} />
            <Route path="/account" element={<ProjectsPage />} />
            <Route path="/p/:token" element={<PublicationPage />} />
            <Route path="*" element={<PrototypePage />} />
          </Routes>
        </BrowserRouter>
      </AppProviders>
    </AppErrorBoundary>
  );
}
