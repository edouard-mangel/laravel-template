import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  fullyParallel: true,
  forbidOnly: !!process.env['CI'],
  retries: process.env['CI'] ? 2 : 0,
  reporter: 'html',

  projects: [
    {
      name: 'api',
      testDir: './playwright/api',
      use: { baseURL: 'http://localhost:8000' },
    },
    {
      name: 'chromium',
      testDir: './playwright',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://localhost:4200',
      },
      webServer: {
        command: 'pnpm start',
        url: 'http://localhost:4200',
        reuseExistingServer: !process.env['CI'],
      },
    },
  ],
});
