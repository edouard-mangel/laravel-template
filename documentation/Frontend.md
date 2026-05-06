# Frontend: Angular 20

Angular 20 + PrimeNG 18 SPA communicating with the Laravel REST API via Sanctum cookies.

---

## Architecture

The Angular app is a standalone SPA in the `client/` directory. It communicates with the Laravel
API over HTTP. Authentication uses Sanctum's cookie-based SPA flow.

```
client/
└── src/app/
    ├── core/
    │   ├── auth/
    │   │   ├── auth.guard.ts              # Route guard for authenticated routes
    │   │   ├── auth.service.ts            # Login, logout, current user
    │   │   └── csrf.interceptor.ts        # CSRF token handling for Sanctum
    │   └── http/
    │       └── error.interceptor.ts       # Global HTTP error handling
    └── features/
        └── product/                       # One folder per domain feature
            ├── product.routes.ts
            ├── models/product.model.ts
            ├── services/
            │   ├── product-reader.service.ts
            │   ├── product-creator.service.ts
            │   └── product-updator.service.ts
            ├── components/
            │   └── product-form.component.ts
            └── pages/
                ├── product-list.page.ts
                └── product-detail.page.ts
```

---

## Standalone Components

All components are standalone (no NgModules). This is the Angular 20 default.

```typescript
@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TableModule, ButtonModule],
  template: `
    <p-table [value]="products()">
      <ng-template pTemplate="body" let-product>
        <tr>
          <td>{{ product.name }}</td>
          <td>{{ product.priceInCents / 100 | currency }}</td>
        </tr>
      </ng-template>
    </p-table>
  `,
})
export class ProductListPage {
  protected readonly products = signal<ProductModel[]>([]);

  constructor(private readonly reader: ProductReaderService) {
    this.loadProducts();
  }

  private loadProducts(): void {
    this.reader.getAll().subscribe(products => this.products.set(products));
  }
}
```

---

## Signals for State

Use Angular 20 signals for reactive state:

```typescript
// Simple signal
const count = signal(0);

// Computed signal (derived state)
const doubled = computed(() => count() * 2);

// Effect (side effects on signal change)
effect(() => console.log('count changed:', count()));

// Update signal
count.update(v => v + 1);
count.set(10);
```

---

## Service Split Pattern

Each feature has separate services for reads and writes. This mirrors the CQRS split in the backend.

```typescript
// product-reader.service.ts — GET operations
@Injectable({ providedIn: 'root' })
export class ProductReaderService {
  constructor(private readonly http: HttpClient) {}

  getAll(filter?: ProductFilter): Observable<ProductModel[]> {
    return this.http.get<ProductModel[]>('/api/products', { params: filter });
  }

  getById(id: string): Observable<ProductModel> {
    return this.http.get<ProductModel>(`/api/products/${id}`);
  }
}

// product-creator.service.ts — POST operations
@Injectable({ providedIn: 'root' })
export class ProductCreatorService {
  constructor(private readonly http: HttpClient) {}

  create(data: CreateProductRequest): Observable<{ id: string }> {
    return this.http.post<{ id: string }>('/api/products', data);
  }
}

// product-updator.service.ts — PUT/PATCH operations
@Injectable({ providedIn: 'root' })
export class ProductUpdatorService {
  constructor(private readonly http: HttpClient) {}

  update(id: string, data: UpdateProductRequest): Observable<void> {
    return this.http.put<void>(`/api/products/${id}`, data);
  }
}
```

---

## Sanctum Authentication Flow

```typescript
// In app.config.ts
export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(withInterceptors([csrfInterceptor])),
    provideRouter(routes),
  ],
};

// csrf.interceptor.ts
export const csrfInterceptor: HttpInterceptorFn = (req, next) => {
  // Get the XSRF-TOKEN cookie and add as header
  const token = document.cookie.match('XSRF-TOKEN=([^;]+)')?.[1];

  if (token && req.method !== 'GET') {
    req = req.clone({
      headers: req.headers.set('X-XSRF-TOKEN', decodeURIComponent(token)),
      withCredentials: true,
    });
  }

  return next(req.clone({ withCredentials: true }));
};
```

Before making any state-changing request, call:

```typescript
// auth.service.ts
login(email: string, password: string): Observable<void> {
  // First, get CSRF cookie
  return this.http.get('/sanctum/csrf-cookie', { withCredentials: true }).pipe(
    switchMap(() => this.http.post('/login', { email, password }, { withCredentials: true })),
    tap(() => this.currentUser.set(/* user data */)),
  );
}
```

---

## OpenAPI Type Generation

Types are generated from the Laravel API's OpenAPI spec:

```bash
# In client/package.json scripts:
"openapi": "openapi-typescript http://localhost:8000/api/documentation.json -o src/app/api/types.ts"

# Run:
pnpm run openapi
```

Use generated types in services:

```typescript
import type { components } from '../api/types';

type ProductModel = components['schemas']['ProductResource'];
type CreateProductRequest = components['schemas']['CreateProductRequest'];
```

---

## Testing

### Unit Tests (Vitest)

```typescript
// product.spec.ts
import { describe, it, expect } from 'vitest';

describe('ProductModel', () => {
  it('formats price as currency', () => {
    const product = { priceInCents: 1999 };
    expect(product.priceInCents / 100).toBeCloseTo(19.99);
  });
});
```

### Integration Tests (Cypress)

```typescript
// cypress/e2e/product.cy.ts
describe('Product management', () => {
  beforeEach(() => cy.login('admin@example.com', 'password'));

  it('creates a new product', () => {
    cy.visit('/products/new');
    cy.get('[data-cy=name]').type('New Widget');
    cy.get('[data-cy=sku]').type('W-NEW');
    cy.get('[data-cy=price]').type('29.99');
    cy.get('[data-cy=submit]').click();
    cy.contains('Product created successfully');
  });
});
```

### E2E Tests (Playwright)

Role-based Playwright projects test the same flows as different users:

```typescript
// playwright.config.ts
projects: [
  { name: 'admin', use: { storageState: 'playwright/.auth/admin.json' } },
  { name: 'viewer', use: { storageState: 'playwright/.auth/viewer.json' } },
  { name: 'unauthenticated' },
]
```

---

## Route Structure

```typescript
// app.routes.ts
export const routes: Routes = [
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },
  {
    path: '',
    canActivate: [authGuard],
    children: [
      { path: 'dashboard', loadComponent: () => import('./features/dashboard/dashboard.page') },
      {
        path: 'products',
        loadChildren: () => import('./features/product/product.routes'),
      },
    ],
  },
  { path: 'login', loadComponent: () => import('./features/auth/login.page') },
];
```

Lazy loading via `loadComponent` and `loadChildren` reduces the initial bundle size.
