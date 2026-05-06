import { defineConfig } from '@playwright/test';

export default defineConfig({
  fullyParallel: true,
  forbidOnly: !!process.env['CI'],
  retries: process.env['CI'] ? 2 : 0,
  reporter: 'html',

  projects: [
    {
      name: 'api',
      testDir: './api',
      use: { baseURL: 'http://localhost:8000' },
    },
  ],
});
