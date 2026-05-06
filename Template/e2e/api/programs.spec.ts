import { test, expect } from '@playwright/test';

test.describe('Programs API', () => {
  let token: string;
  let programId: string;

  test.beforeAll(async ({ request }) => {
    const res = await request.post('/api/login', {
      data: { email: 'test@example.com', password: 'password' },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.token).toBeTruthy();
    token = body.token;
  });

  test('creates a program', async ({ request }) => {
    const res = await request.post('/api/programs', {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        title: 'Planet Earth III',
        description: 'A stunning nature documentary series.',
        duration_minutes: 60,
        genre: 'documentary',
      },
    });

    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.id).toBeTruthy();
    programId = body.id;
  });

  test('retrieves the program by id', async ({ request }) => {
    const res = await request.get(`/api/programs/${programId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.title).toBe('Planet Earth III');
    expect(body.genre).toBe('documentary');
    expect(body.duration_minutes).toBe(60);
    expect(body.description).toBe('A stunning nature documentary series.');
  });

  test('lists programs with pagination meta', async ({ request }) => {
    const res = await request.get('/api/programs', {
      headers: { Authorization: `Bearer ${token}` },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body.data)).toBe(true);
    expect(body.meta.total).toBeGreaterThanOrEqual(1);
  });

  test('updates the program', async ({ request }) => {
    const res = await request.put(`/api/programs/${programId}`, {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        title: 'Planet Earth III (Extended)',
        description: 'Updated description.',
        duration_minutes: 75,
      },
    });

    expect(res.status()).toBe(204);
  });

  test('retrieves the updated program', async ({ request }) => {
    const res = await request.get(`/api/programs/${programId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.title).toBe('Planet Earth III (Extended)');
    expect(body.duration_minutes).toBe(75);
  });

  test('deletes the program', async ({ request }) => {
    const res = await request.delete(`/api/programs/${programId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    expect(res.status()).toBe(204);
  });

  test('returns 404 for deleted program', async ({ request }) => {
    const res = await request.get(`/api/programs/${programId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    expect(res.status()).toBe(404);
  });

  test('returns 401 for wrong password', async ({ request }) => {
    const res = await request.post('/api/login', {
      data: { email: 'test@example.com', password: 'wrong-password' },
    });

    expect(res.status()).toBe(401);
    const body = await res.json();
    expect(body.message).toBe('Invalid credentials.');
  });
});
