import { render, screen } from '@testing-library/react';
import { expect, it } from 'vitest';
import { CabinetDrawing } from './CabinetDrawing';
import { defaultCabinet } from '../model/configuration';
it('draws the configured sections and changes the facade view', () => {
  const { container, rerender } = render(<CabinetDrawing configuration={defaultCabinet} closed={false} />);
  expect(screen.getByRole('img', { name: /2400 × 2400 × 600/ })).toBeInTheDocument();
  expect(container.querySelectorAll('[data-section]')).toHaveLength(3);
  expect(container.querySelectorAll('[data-drawer]')).toHaveLength(2);
  rerender(<CabinetDrawing configuration={defaultCabinet} closed />);
  expect(container.querySelectorAll('[data-door]')).toHaveLength(3);
  expect(container.querySelectorAll('[data-drawer]')).toHaveLength(0);
});
it('keeps the maximum filling inside a short cabinet', () => {
  const { container } = render(<CabinetDrawing configuration={{ ...defaultCabinet, height: 400, sections: [{ type: 'drawers', shelves: 12, drawers: 6 }] }} closed={false} />);
  for (const rectangle of container.querySelectorAll('[data-section] rect')) {
    const top = Number(rectangle.getAttribute('y'));
    expect(top).toBeGreaterThanOrEqual(18);
    expect(top + Number(rectangle.getAttribute('height'))).toBeLessThanOrEqual(382);
  }
});
