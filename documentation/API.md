# API Design

REST endpoints following Laravel conventions with API Resources for response shaping.

---

## Route Registration

All API routes live in `routes/api.php` and are prefixed with `/api` automatically:

```php
// routes/api.php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('products', ProductController::class);
    // Expands to:
    // GET    /api/products           → index
    // POST   /api/products           → store
    // GET    /api/products/{product} → show
    // PUT    /api/products/{product} → update
    // DELETE /api/products/{product} → destroy
});
```

---

## Controller Structure

Controllers are thin. Each method:
1. Accepts a typed FormRequest (validation + authorization)
2. Calls one Action or Query
3. Returns an API Resource

```php
final class ProductController extends Controller
{
    public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $productId = $action->handle(CreateProductData::fromRequest($request));

        return response()->json(['id' => (string) $productId], 201);
    }

    public function show(string $id, GetProductByIdQuery $query): ProductResource|JsonResponse
    {
        $product = $query->handle(ProductId::fromString($id));

        if ($product === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return new ProductResource($product);
    }
}
```

---

## HTTP Status Codes

| Scenario | Code |
|----------|------|
| Created | 201 |
| No content (update/delete) | 204 |
| Not found | 404 |
| Validation error | 422 |
| Unauthorized | 401 |
| Forbidden | 403 |
| Server error | 500 |

---

## API Resources

Resources control the JSON response shape. They wrap DTOs, not Eloquent models:

```php
final class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProductDto $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'sku' => $dto->sku,
            'price_in_cents' => $dto->priceInCents,
            'price_formatted' => '$' . number_format($dto->priceInCents / 100, 2),
            'created_at' => $dto->createdAt->format('c'),
        ];
    }
}
```

For collections with pagination metadata, use `Resource::collection()`:

```php
return ProductResource::collection($paginatedProducts);
// Automatically includes pagination links and meta
```

---

## Form Requests

Form Requests handle validation and authorization together:

```php
final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Authenticated
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'price_in_cents' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use.',
        ];
    }
}
```

Failed validation automatically returns a 422 response with field errors.

---

## Error Response Format

Laravel's default 422 format:

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

Domain exceptions (business rule violations) are mapped to HTTP responses in the exception handler:

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->renderable(function (InvalidInputException $e, Request $request) {
        return response()->json(['message' => $e->getMessage()], 422);
    });

    $this->renderable(function (ResourceNotFoundException $e, Request $request) {
        return response()->json(['message' => $e->getMessage()], 404);
    });
}
```

---

## Filtering and Pagination

Accept filter parameters as query strings:

```
GET /api/products?name=widget&page=2&per_page=20
```

Build a typed filter from the request:

```php
public function index(Request $request, GetProductsQuery $query): AnonymousResourceCollection
{
    $filter = ProductFilter::fromRequest($request);
    $products = $query->handle($filter);

    return ProductResource::collection($products);
}
```

Pagination response format (automatic when returning a `LengthAwarePaginator`):

```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost/api/products?page=1",
    "last": "http://localhost/api/products?page=5",
    "prev": null,
    "next": "http://localhost/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 73
  }
}
```

---

## OpenAPI Documentation

Use `darkaonline/l5-swagger` or `knuckleswtf/scribe` to generate an OpenAPI spec from PHPDoc
annotations. The spec can be consumed by `openapi-typescript` or any OpenAPI client generator.

```bash
php artisan l5-swagger:generate   # Generate spec
# Access at: http://localhost:8000/api/documentation
```

Download the spec:

```bash
curl http://localhost:8000/api/documentation.json -o openapi.json
```
