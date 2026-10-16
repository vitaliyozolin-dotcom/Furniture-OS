import { afterEach, expect, it, vi } from 'vitest';
import { z } from 'zod';
import { request, remove } from './request';
import { sessionApi } from '../../features/session';
import { workspaceApi } from '../../features/cabinet-workspace/api';
import { account, anonymous, project, publication, estimate } from '../../test/pilot/fixtures';
import { defaultCabinet } from '../../entities/cabinet/model/configuration';
afterEach(() => vi.restoreAllMocks());
it('uses generated pilot contracts and obtains fresh CSRF for all mutations', async () => {
  const calls: { url: string; options?: RequestInit }[] = [];
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (input, options) => {
    const url = String(input); calls.push({ url, options });
    if (url === '/api/auth/session') return Response.json(anonymous);
    if (url === '/api/auth/register' || url === '/api/auth/login') return Response.json(account);
    if (url === '/api/auth/logout' || options?.method === 'DELETE') return new Response(null, { status: 204 });
    if (url === '/api/cabinets/preview') return Response.json({ configuration: defaultCabinet, estimate, rulesetVersion: 'prototype-v0' });
    if (url.includes('publication')) return Response.json(publication);
    return Response.json(url === '/api/projects' && !options?.method ? [project] : project);
  });
  expect(await sessionApi.signIn('register', { name: account.name, email: account.email, password: 'Password123!' })).toEqual(account);
  await sessionApi.signIn('login', { name: '', email: account.email, password: 'Password123!' }); await sessionApi.logout();
  expect(await workspaceApi.preview(defaultCabinet)).toHaveProperty('estimate'); expect(await workspaceApi.projects()).toEqual([project]); expect(await workspaceApi.project(project.id)).toEqual(project);
  await workspaceApi.save(null, project.title, defaultCabinet); await workspaceApi.save(project, project.title, defaultCabinet); await workspaceApi.publish(project, publication.city, publication.description); await workspaceApi.withdraw(project.id); expect(await workspaceApi.publication(publication.token)).toEqual(publication);
  for (const call of calls.filter((item) => ['PUT', 'DELETE'].includes(item.options?.method ?? '') || item.url.endsWith('/login') || item.url.endsWith('/register'))) expect(call.options?.headers).toHaveProperty('X-CSRF-Token', anonymous.csrfToken);
});
it.each([{ detail: 'Конфликт версии' }, { detail: 1 }, null, 'error'])('handles rejected API responses safely', async (problem) => {
  vi.spyOn(globalThis, 'fetch').mockResolvedValue(Response.json(problem, { status: 409 })); await expect(request('/bad', z.unknown())).rejects.toThrow(typeof problem === 'object' && problem !== null && typeof problem.detail === 'string' ? problem.detail : 'Ошибка запроса (409)');
});
it('rejects malformed success payloads and failed logout/withdrawal', async () => {
  vi.spyOn(globalThis, 'fetch').mockResolvedValue(Response.json({})); await expect(sessionApi.session()).rejects.toThrow();
  vi.mocked(fetch).mockResolvedValueOnce(Response.json(anonymous)).mockResolvedValueOnce(new Response(null, { status: 500 })); await expect(sessionApi.logout()).rejects.toThrow('Не удалось выйти');
  vi.mocked(fetch).mockResolvedValue(new Response(null, { status: 500 })); await expect(remove('/publication', 'token')).rejects.toThrow('Не удалось снять публикацию');
});
