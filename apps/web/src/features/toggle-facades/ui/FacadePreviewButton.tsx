import { useState } from 'react';
import { CabinetPreview } from '../../../entities/cabinet/ui/CabinetPreview';

export function FacadePreviewButton() {
  const [open, setOpen] = useState(false);

  return (
    <button className="preview-button" onClick={() => setOpen((value) => !value)} aria-label="Открыть или закрыть фасады">
      <CabinetPreview open={open} />
      <span className="preview-hint">{open ? 'Закрыть фасады' : 'Нажмите, чтобы открыть'}</span>
    </button>
  );
}
