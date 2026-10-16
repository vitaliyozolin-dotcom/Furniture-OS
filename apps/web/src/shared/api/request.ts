import type { z } from 'zod';

export async function request<T>(path: string, schema: z.ZodType<T>, options: RequestInit = {}): Promise<T> {
  const response = await fetch(path, { credentials: 'same-origin', ...options });
  if (!response.ok) {
    const problem: unknown = await response.json();
    const detail = typeof problem === 'object' && problem !== null && 'detail' in problem && typeof problem.detail === 'string' ? problem.detail : `Ошибка запроса (${response.status})`;
    throw new Error(detail);
  }
  return schema.parse(await response.json());
}

export async function remove(path: string, csrfToken: string): Promise<void> {
  const response = await fetch(path, { method: 'DELETE', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } });
  if (!response.ok) throw new Error('Не удалось снять публикацию. Попробуйте ещё раз.');
}

export function jsonBody(method: string, body: unknown, csrfToken: string): RequestInit {
  return { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(body) };
}
