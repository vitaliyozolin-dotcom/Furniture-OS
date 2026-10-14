import { expect, test } from '@playwright/test';

test('shows the current estimate and supports the primary interactions', async ({ page }) => {
  const readiness = await page.request.get('/health/ready');
  expect(readiness.status()).toBe(200);
  const calculation = page.waitForResponse((response) => response.url().includes('/api/prototype/v0/calculate') && response.request().method() === 'POST');
  await page.goto('./');

  const response = await calculation;
  expect(response.status()).toBe(200);
  expect(response.headers()['x-correlation-id']).toBeTruthy();
  await expect(page.getByRole('heading', { name: 'Создайте шкаф за 3 минуты' })).toBeVisible();
  await expect(page.getByText(/64.302 ₽/)).toBeVisible();
  await expect(page.getByText('Конструкция проверена')).toBeVisible();

  await page.getByRole('button', { name: 'Открыть или закрыть фасады' }).click();
  await expect(page.getByText('Закрыть фасады')).toBeVisible();

  await page.getByRole('button', { name: /Продолжить создание/ }).click();
  await expect(page.getByRole('button', { name: /Переходим к размерам/ })).toBeVisible();
});

test('matches the prototype visual baseline', async ({ page }) => {
  await page.goto('./');
  await expect(page.getByText(/64.302 ₽/)).toBeVisible();
  await expect(page).toHaveScreenshot('prototype-home.png', { fullPage: true });
});

test('shows API failure without substituting a local price', async ({ page }) => {
  await page.route('**/api/prototype/v0/calculate', (route) => route.fulfill({
    status: 503,
    contentType: 'application/problem+json',
    body: JSON.stringify({ type: 'about:blank', title: 'Unavailable', status: 503, detail: 'Test outage', correlationId: 'e2e-outage' }),
  }));
  await page.goto('./');
  await expect(page.getByRole('heading', { name: 'Расчёт недоступен' })).toBeVisible();
  await expect(page.getByText(/64.302 ₽/)).toHaveCount(0);
});
