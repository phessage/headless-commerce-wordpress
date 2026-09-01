import { expect, test } from '@playwright/test';

test('WordPress UI completes a deployed non-hosted order', async ({ page }) => {
  if (!process.env.WORDPRESS_LIVE_URL) throw new Error('WORDPRESS_LIVE_URL is required');
  await page.goto(process.env.WORDPRESS_LIVE_URL);
  await expect(page.getByRole('heading', { name: 'Products' })).toBeVisible();
  const product = page.locator('.onecomm-product').filter({ has: page.locator('input[name="product_id"][value="1f7884bd-759d-4f47-9fdb-c7ea3dd3a9ef"]') });
  await expect(product.getByRole('button', { name: 'Add to cart' })).toBeVisible();
  await product.getByRole('button', { name: 'Add to cart' }).click();
  await expect(page.locator('.onecomm-cart .onecomm-line')).toHaveCount(1);

  const checkout = page.locator('.onecomm-checkout');
  await checkout.getByLabel('First Name').fill('Headless');
  await checkout.getByLabel('Last Name').fill('Fixture');
  await checkout.getByLabel('Email').fill('wordpress-browser@example.test');
  await checkout.getByLabel('Address1').fill('1 Test Way');
  await checkout.getByLabel('City').fill('Vancouver');
  await checkout.getByLabel('State').fill('BC');
  await checkout.getByLabel('Postal Code').fill('V6B1A1');
  await checkout.getByLabel('Country').fill('CA');
  await checkout.getByRole('button', { name: 'Save checkout details' }).click();

  const shippingButton = page.locator('.onecomm-checkout h3', { hasText: 'Shipping' }).locator('xpath=following-sibling::form[1]').getByRole('button');
  await expect(shippingButton).toBeVisible();
  await shippingButton.click();
  const paymentButton = page.locator('.onecomm-checkout h3', { hasText: 'Payment method' }).locator('xpath=following-sibling::form[1]').getByRole('button');
  await expect(paymentButton).toBeVisible();
  await paymentButton.click();
  await expect(page.locator('.onecomm-readiness')).toContainText('Checkout is prepared');
  await page.getByRole('button', { name: 'Place pending order' }).click();
  const confirmation = page.locator('.onecomm-order-confirmation');
  await expect(confirmation).toContainText(/Order ORD\d+ placed/);
  await expect(page.locator('.onecomm-order-confirmation')).toContainText('Payment: pending');
  const heading = await confirmation.getByRole('heading').innerText();
  const orderNumber = heading.match(/ORD\d+/)?.[0];
  expect(orderNumber).toBeTruthy();
  await page.reload();
  await page.getByLabel('Order number').fill(orderNumber!);
  await page.getByLabel('Order email').fill('wordpress-browser@example.test');
  await page.getByRole('button', { name: 'Check order status' }).click();
  await expect(page.locator('.onecomm-order-result')).toContainText(`Order ${orderNumber}`);
  await expect(page.locator('.onecomm-order-result')).toContainText('Payment: pending');
  await expect(page.locator('.onecomm-order-result')).toContainText('Items: 1');
  console.log(`WordPress live UI create/reopen ${orderNumber}`);
});
