import { afterEach, describe, expect, it } from 'vitest';
import { useDraftStore } from './draftStore';

afterEach(() => useDraftStore.getState().reset());

describe('draftStore', () => {
  it('keeps draft history for undo and redo', () => {
    const initialWidth = useDraftStore.getState().draft.width;

    useDraftStore.getState().updateDraft({ width: 1800 });
    expect(useDraftStore.getState().draft.width).toBe(1800);

    useDraftStore.getState().undo();
    expect(useDraftStore.getState().draft.width).toBe(initialWidth);

    useDraftStore.getState().redo();
    expect(useDraftStore.getState().draft.width).toBe(1800);
  });

  it('keeps UI navigation separate from the draft', () => {
    const initialDraft = useDraftStore.getState().draft;

    useDraftStore.getState().setCurrentStep(3);
    useDraftStore.getState().startJourney();

    expect(useDraftStore.getState()).toMatchObject({
      currentStep: 3,
      journeyStarted: true,
      draft: initialDraft,
    });
  });
});
