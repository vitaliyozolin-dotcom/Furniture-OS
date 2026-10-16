import { z } from 'zod';
import type { components } from '../../shared/api/generated/schema';
import { jsonBody, remove, request } from '../../shared/api/request';
import { calculationResultSchema } from '../../entities/calculation/model/calculationResultSchema';
import { configurationSchema, type CabinetConfiguration } from '../../entities/cabinet/model/configuration';
import { sessionApi } from '../session';
export type CabinetProject = components['schemas']['CabinetProject'];
export type CabinetPublication = components['schemas']['CabinetPublication'];
const projectSchema = z.object({ id: z.string(), title: z.string(), configuration: configurationSchema, version: z.number().int(), updatedAt: z.string() }) satisfies z.ZodType<CabinetProject>;
const previewSchema = z.object({ configuration: configurationSchema, estimate: calculationResultSchema, rulesetVersion: z.literal('prototype-v0') });
const publicationSchema = z.object({ token: z.string(), title: z.string(), city: z.string(), description: z.string(), configuration: configurationSchema, estimate: calculationResultSchema, rulesetVersion: z.literal('prototype-v0'), publishedAt: z.string() }) satisfies z.ZodType<CabinetPublication>;
export const workspaceApi = {
  preview: (configuration: CabinetConfiguration) => request('/api/cabinets/preview', previewSchema, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(configuration) }),
  projects: () => request('/api/projects', z.array(projectSchema)),
  project: (id: string) => request(`/api/projects/${encodeURIComponent(id)}`, projectSchema),
  async save(project: CabinetProject | null, title: string, configuration: CabinetConfiguration) {
    const { csrfToken } = await sessionApi.session();
    const body = project ? { title, configuration, expectedVersion: project.version } : { title, configuration };
    return request(project ? `/api/projects/${encodeURIComponent(project.id)}` : '/api/projects', projectSchema, jsonBody(project ? 'PUT' : 'POST', body, csrfToken));
  },
  async publish(project: CabinetProject, city: string, description: string) {
    const { csrfToken } = await sessionApi.session();
    return request(`/api/projects/${encodeURIComponent(project.id)}/publication`, publicationSchema, jsonBody('POST', { city, description, expectedVersion: project.version }, csrfToken));
  },
  async withdraw(id: string) {
    const { csrfToken } = await sessionApi.session();
    await remove(`/api/projects/${encodeURIComponent(id)}/publication`, csrfToken);
  },
  publication: (token: string) => request(`/api/publications/${encodeURIComponent(token)}`, publicationSchema),
};
