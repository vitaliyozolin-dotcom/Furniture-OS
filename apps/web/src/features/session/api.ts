import { z } from 'zod';
import type { components } from '../../shared/api/generated/schema';
import { jsonBody, request } from '../../shared/api/request';

export type Account = components['schemas']['Account'];
export const accountSchema = z.object({ id: z.string(), email: z.string(), name: z.string() }) satisfies z.ZodType<Account>;
const sessionSchema = z.object({ user: accountSchema.nullable(), csrfToken: z.string().min(1) });
export const sessionApi = {
  session: () => request('/api/auth/session', sessionSchema),
  async signIn(mode: 'login' | 'register', fields: components['schemas']['Registration']) {
    if (mode === 'register') {
      const session = await sessionApi.session();
      await request('/api/auth/register', accountSchema, jsonBody('POST', fields, session.csrfToken));
    }
    const session = await sessionApi.session();
    return request('/api/auth/login', accountSchema, jsonBody('POST', { email: fields.email, password: fields.password }, session.csrfToken));
  },
  async logout() {
    const session = await sessionApi.session();
    const response = await fetch('/api/auth/logout', { credentials: 'same-origin', ...jsonBody('POST', {}, session.csrfToken) });
    if (!response.ok) throw new Error('Не удалось выйти. Попробуйте ещё раз.');
  },
};
