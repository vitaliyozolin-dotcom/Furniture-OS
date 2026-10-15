import { Component, type ErrorInfo, type ReactNode } from 'react';
import { PrototypeStatusView } from '../shared/ui/PrototypeStatusView';

interface Props {
  children: ReactNode;
}

interface State {
  failed: boolean;
}

export class AppErrorBoundary extends Component<Props, State> {
  state: State = { failed: false };

  static getDerivedStateFromError(): State {
    return { failed: true };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error('Furniture OS render failed', error, info);
  }

  render() {
    if (this.state.failed) {
      return <PrototypeStatusView status="error" />;
    }

    return this.props.children;
  }
}
