import { afterEach, describe, expect, it } from 'vitest';
import { useDraftStore } from './draftStore';

afterEach(() => useDraftStore.getState().reset());

describe('draftStore', () => {
  it('ignores undo/redo without history and discards a branched future', () => {
    const initial = useDraftStore.getState().draft;
    useDraftStore.getState().undo();
    useDraftStore.getState().redo();
    expect(useDraftStore.getState().draft).toBe(initial);
    useDraftStore.getState().updateDraft({ width: 1800 });
    useDraftStore.getState().undo();
    useDraftStore.getState().updateDraft({ width: 1900 });
    useDraftStore.getState().redo();
    expect(useDraftStore.getState().draft.width).toBe(1900);
    useDraftStore.getState().setCurrentStep(-1);
    expect(useDraftStore.getState().currentStep).toBe(1);
    useDraftStore.getState().setCurrentStep(99);
    expect(useDraftStore.getState().currentStep).toBe(4);
  });
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
