import { create } from 'zustand';
import type { CabinetConfig } from '../../../domain/types';
import { prototypeCabinetConfig } from '../../../pages/prototypeCabinetConfig';

interface DraftState {
  draft: CabinetConfig;
  currentStep: number;
  journeyStarted: boolean;
  past: CabinetConfig[];
  future: CabinetConfig[];
  updateDraft: (changes: Partial<CabinetConfig>) => void;
  undo: () => void;
  redo: () => void;
  setCurrentStep: (step: number) => void;
  startJourney: () => void;
  reset: () => void;
}

const initialState = () => ({
  draft: structuredClone(prototypeCabinetConfig),
  currentStep: 1,
  journeyStarted: false,
  past: [],
  future: [],
});

export const useDraftStore = create<DraftState>((set) => ({
  ...initialState(),
  updateDraft: (changes) => set((state) => ({
    draft: { ...state.draft, ...changes },
    past: [...state.past, state.draft],
    future: [],
  })),
  undo: () => set((state) => {
    const previous = state.past.at(-1);
    if (!previous) return state;

    return {
      draft: previous,
      past: state.past.slice(0, -1),
      future: [state.draft, ...state.future],
    };
  }),
  redo: () => set((state) => {
    const next = state.future[0];
    if (!next) return state;

    return {
      draft: next,
      past: [...state.past, state.draft],
      future: state.future.slice(1),
    };
  }),
  setCurrentStep: (step) => set({ currentStep: Math.min(4, Math.max(1, step)) }),
  startJourney: () => set({ journeyStarted: true }),
  reset: () => set(initialState()),
}));
