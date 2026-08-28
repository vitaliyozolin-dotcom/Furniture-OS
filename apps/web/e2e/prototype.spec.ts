import { expect, test } from '@playwright/test';

test('shows the current estimate and supports the primary interactions', async ({ page }) => {
  await page.goto('./');

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
  await expect(page).toHaveScreenshot('prototype-home.png', { fullPage: true });
});
