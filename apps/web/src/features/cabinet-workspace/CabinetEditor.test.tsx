import { fireEvent, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { CabinetEditor, pendingCabinet } from './CabinetEditor';
import { workspaceApi } from './api';
import { sessionApi } from '../session';
import { renderPilot } from '../../test/pilot/render';
import { anonymous, signedIn, project, publication, estimate } from '../../test/pilot/fixtures';
import { defaultCabinet } from '../../entities/cabinet/model/configuration';
beforeEach(() => {
  sessionStorage.clear(); vi.spyOn(sessionApi, 'session').mockResolvedValue(signedIn);
  vi.spyOn(workspaceApi, 'preview').mockImplementation(async (configuration) => ({ configuration, estimate, rulesetVersion: 'prototype-v0' }));
  vi.spyOn(workspaceApi, 'save').mockImplementation(async (existing, title, configuration) => ({ ...project, title, configuration, version: existing ? existing.version + 1 : 1 }));
  vi.spyOn(workspaceApi, 'publish').mockResolvedValue(publication); vi.spyOn(workspaceApi, 'withdraw').mockResolvedValue();
});
afterEach(() => vi.restoreAllMocks());
const tab = (name: string) => fireEvent.click(screen.getByRole('button', { name }));
async function ready() { await waitFor(() => expect(screen.getByRole('button', { name: 'Сохранить проект' })).toBeEnabled()); }
function publishForm() {
  tab('4. Публикация'); fireEvent.change(screen.getByLabelText('Город'), { target: { value: 'Москва' } }); fireEvent.change(screen.getByLabelText('Пожелания'), { target: { value: 'Для прихожей' } }); fireEvent.click(screen.getByRole('checkbox'));
  return screen.getByLabelText('Город').closest('form') as HTMLFormElement;
}
it('edits dimensions, sections and materials with undo/redo and bounded controls', async () => {
  renderPilot(<CabinetEditor project={project} />); await ready();
  for (const [label, value] of [['Ширина, мм', '2600'], ['Высота, мм', '2300'], ['Глубина, мм', '650']]) fireEvent.change(screen.getByLabelText(label), { target: { value } });
  tab('Отменить'); tab('Повторить'); tab('Показать фасады'); tab('Показать наполнение'); tab('Далее');
  fireEvent.change(screen.getByLabelText('Тип секции 1'), { target: { value: 'drawers' } }); fireEvent.change(screen.getByLabelText('Полки, секция 1'), { target: { value: '2' } }); fireEvent.change(screen.getByLabelText('Ящики, секция 1'), { target: { value: '3' } });
  tab('Добавить секцию'); tab('Добавить секцию'); expect(screen.getByRole('button', { name: 'Добавить секцию' })).toBeDisabled();
  for (let count = 5; count > 1; count -= 1) tab(`Удалить секцию ${count}`);
  expect(screen.getByRole('button', { name: 'Удалить секцию 1' })).toBeDisabled(); tab('Далее');
  fireEvent.change(screen.getByLabelText('Корпус'), { target: { value: 'graphite' } }); fireEvent.change(screen.getByLabelText('Фасады'), { target: { value: 'mirror' } }); tab('Назад');
  await ready(); expect(screen.getByRole('img', { name: /2600 × 2300 × 650/ })).toBeInTheDocument();
  fireEvent.change(screen.getByLabelText('Название проекта'), { target: { value: 'Мой вариант' } }); fireEvent.submit(screen.getByLabelText('Название проекта').closest('form') as HTMLFormElement);
  expect(await screen.findByText('Проект сохранён')).toBeInTheDocument(); expect(workspaceApi.save).toHaveBeenCalledWith(project, 'Мой вариант', expect.objectContaining({ width: 2600, bodyMaterial: 'graphite', facadeMaterial: 'mirror' }));
});
it('preserves an anonymous draft before signing in or registering', async () => {
  vi.mocked(sessionApi.session).mockResolvedValue(anonymous); renderPilot(<CabinetEditor project={null} />);
  fireEvent.change(screen.getByLabelText('Ширина, мм'), { target: { value: '2700' } }); fireEvent.click(await screen.findByRole('link', { name: 'Войти и сохранить шкаф' }));
  expect(JSON.parse(sessionStorage.getItem('furniture-pending-cabinet') as string).configuration.width).toBe(2700); expect(screen.getByTestId('location')).toHaveTextContent('/login?next=constructor');
  tab('4. Публикация'); fireEvent.click(screen.getByRole('link', { name: 'Зарегистрироваться для публикации' })); expect(screen.getByTestId('location')).toHaveTextContent('/register?next=constructor');
});
it('saves a new project and publishes the saved version without another write', async () => {
  renderPilot(<CabinetEditor project={null} />); await ready();
  fireEvent.submit(screen.getByLabelText('Название проекта').closest('form') as HTMLFormElement);
  await waitFor(() => expect(screen.getByTestId('location')).toHaveTextContent(`/constructor/${project.id}`));
  fireEvent.submit(screen.getByLabelText('Название проекта').closest('form') as HTMLFormElement);
  await waitFor(() => expect(screen.getByRole('button', { name: 'Сохранить проект' })).toBeEnabled()); expect(workspaceApi.save).toHaveBeenCalledTimes(1);
  fireEvent.submit(publishForm()); await waitFor(() => expect(screen.getByTestId('location')).toHaveTextContent(`/p/${publication.token}`));
  expect(workspaceApi.publish).toHaveBeenCalledWith(expect.objectContaining({ version: 1 }), 'Москва', 'Для прихожей');
});
it('publishes a new draft by saving it first', async () => {
  renderPilot(<CabinetEditor project={null} />); await ready(); fireEvent.submit(publishForm());
  await waitFor(() => expect(workspaceApi.publish).toHaveBeenCalled()); expect(workspaceApi.save).toHaveBeenCalledTimes(1);
});
it('withdraws a publication from a saved project', async () => {
  renderPilot(<CabinetEditor project={project} />); await ready(); tab('4. Публикация'); tab('Снять публикацию'); expect(await screen.findByText('Публикация снята')).toBeInTheDocument(); expect(workspaceApi.withdraw).toHaveBeenCalledWith(project.id);
});
it.each(['save', 'publish', 'withdraw'] as const)('reports %s failure and restores the controls', async (operation) => {
  vi.mocked(workspaceApi[operation]).mockRejectedValue(new Error('Сервис недоступен'));
  renderPilot(<CabinetEditor project={project} />); await ready();
  if (operation === 'save') { fireEvent.change(screen.getByLabelText('Название проекта'), { target: { value: 'Другой шкаф' } }); fireEvent.submit(screen.getByLabelText('Название проекта').closest('form') as HTMLFormElement); }
  else if (operation === 'publish') fireEvent.submit(publishForm());
  else { tab('4. Публикация'); tab('Снять публикацию'); }
  expect(await screen.findByRole('alert')).toHaveTextContent('Сервис недоступен'); await ready();
  fireEvent.change(screen.getByLabelText('Ширина, мм'), { target: { value: '2500' } }); expect(screen.queryByText('Сервис недоступен')).not.toBeInTheDocument();
});
it.each(['save', 'publish', 'withdraw'] as const)('handles an unknown %s error', async (operation) => {
  vi.mocked(workspaceApi[operation]).mockRejectedValue('offline'); renderPilot(<CabinetEditor project={project} />); await ready();
  if (operation === 'save') { fireEvent.change(screen.getByLabelText('Название проекта'), { target: { value: 'Другой шкаф' } }); fireEvent.submit(screen.getByLabelText('Название проекта').closest('form') as HTMLFormElement); }
  else if (operation === 'publish') fireEvent.submit(publishForm()); else { tab('4. Публикация'); tab('Снять публикацию'); }
  expect(await screen.findByRole('alert')).toHaveTextContent('Не удалось');
});
it('does not enable saving when the preview fails', async () => {
  vi.mocked(workspaceApi.preview).mockRejectedValue(new Error('offline')); renderPilot(<CabinetEditor project={project} />); expect(await screen.findByRole('alert')).toHaveTextContent('Расчёт недоступен'); expect(screen.getByRole('button', { name: 'Сохранить проект' })).toBeDisabled();
});
it('loads a valid pending cabinet and recovers from malformed stored values', () => {
  sessionStorage.setItem('furniture-pending-cabinet', JSON.stringify({ title: 'Черновик', configuration: defaultCabinet })); expect(pendingCabinet().title).toBe('Черновик');
  for (const value of ['{bad', 'null', '{}', '{"title":1}', '{"title":"Черновик","configuration":{}}']) { sessionStorage.setItem('furniture-pending-cabinet', value); expect(pendingCabinet().configuration).toEqual(defaultCabinet); }
  sessionStorage.clear(); expect(pendingCabinet().title).toBe('Мой шкаф');
});
it('shows server warnings without claiming production correctness', async () => {
  vi.mocked(workspaceApi.preview).mockResolvedValue({ configuration: defaultCabinet, estimate: { ...estimate, warnings: ['Секция требует проверки технологом'] }, rulesetVersion: 'prototype-v0' });
  renderPilot(<CabinetEditor project={null} />); expect(await screen.findByText('Секция требует проверки технологом')).toBeInTheDocument();
});
