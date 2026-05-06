# Program Domain + E2E Test Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the `Product` worked example with a `Program` (TV domain) aggregate throughout `Template/`, add a `POST /api/login` auth endpoint, and add a Playwright API E2E test covering the full CRUD cycle.

**Architecture:** Clean Architecture layers are preserved — Domain stays pure PHP, Application holds Actions/Queries/Contracts, Infrastructure holds Eloquent models, HTTP layer is thin. The replacement is a straight rename: every `Product*` file becomes a `Program*` file with domain-appropriate field names (`title`, `description`, `duration_minutes`, `genre` instead of `name`, `sku`, `price_in_cents`).

**Tech Stack:** PHP 8.4+, Laravel 12, Pest 3, Ramsey UUID, Playwright (TypeScript, `request` fixture — no browser)

---

## Working directory

All PHP paths are relative to `Template/`. All client paths are relative to `Template/client/`.

## Test runner (PHP)

```bash
docker run --rm \
  -v "${PWD}/Template:/app" -w /app \
  -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" \
  -e DB_CONNECTION=sqlite -e "DB_DATABASE=:memory:" \
  -e CACHE_STORE=array -e QUEUE_CONNECTION=sync \
  -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false \
  laravel-test:local \
  php -d memory_limit=512M -d display_errors=stderr \
  vendor/bin/pest --no-coverage
```

Save this as an alias: `alias pest-run='docker run ...'` or run it in full each time.

---

## Task 1: Delete all Product files

**Files:** Remove ~14 files

- [ ] **Step 1: Delete Product domain, application, infrastructure, HTTP, database and test files**

```bash
cd Template

rm -rf app/Domain/Product
rm -rf app/Application/Product
rm -f  app/Application/Contracts/Repositories/ProductRepositoryInterface.php
rm -f  app/Application/Contracts/Finders/ProductFinderInterface.php
rm -rf app/Infrastructure/Persistence/Product
rm -f  app/Http/Controllers/ProductController.php
rm -f  app/Http/Requests/CreateProductRequest.php
rm -f  app/Http/Requests/UpdateProductRequest.php
rm -f  app/Http/Resources/ProductResource.php
rm -f  database/migrations/2026_01_01_000001_create_products_table.php
rm -f  database/factories/ProductFactory.php
rm -rf tests/Unit/Product
rm -rf tests/Feature/Product
```

- [ ] **Step 2: Commit the deletion**

```bash
git add -A
git commit -m "chore: delete Product worked example"
```

---

## Task 2: Domain value objects + failing unit test

**Files:**
- Create: `app/Domain/Program/ProgramId.php`
- Create: `app/Domain/Program/ProgramTitle.php`
- Create: `app/Domain/Program/ProgramDescription.php`
- Create: `app/Domain/Program/ProgramDuration.php`
- Create: `app/Domain/Program/ProgramGenre.php`
- Create: `tests/Unit/Program/ProgramTest.php`

- [ ] **Step 1: Write the failing unit test first**

Create `tests/Unit/Program/ProgramTest.php`:

```php
<?php

use App\Domain\Program\ProgramTitle;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Shared\Exceptions\InvalidInputException;

describe('ProgramTitle', function (): void {
    it('rejects empty titles', function (): void {
        expect(fn () => new ProgramTitle(''))->toThrow(InvalidInputException::class);
    });

    it('rejects titles over 255 characters', function (): void {
        expect(fn () => new ProgramTitle(str_repeat('a', 256)))->toThrow(InvalidInputException::class);
    });

    it('accepts a valid title', function (): void {
        expect((new ProgramTitle('Planet Earth III'))->value)->toBe('Planet Earth III');
    });
});

describe('ProgramDuration', function (): void {
    it('rejects zero duration', function (): void {
        expect(fn () => new ProgramDuration(0))->toThrow(InvalidInputException::class);
    });

    it('rejects negative duration', function (): void {
        expect(fn () => new ProgramDuration(-1))->toThrow(InvalidInputException::class);
    });

    it('accepts positive duration', function (): void {
        expect((new ProgramDuration(60))->minutes)->toBe(60);
    });
});

describe('ProgramDescription', function (): void {
    it('accepts null', function (): void {
        expect((new ProgramDescription(null))->value)->toBeNull();
    });

    it('accepts empty string', function (): void {
        expect((new ProgramDescription(''))->value)->toBe('');
    });

    it('rejects description over 2000 characters', function (): void {
        expect(fn () => new ProgramDescription(str_repeat('a', 2001)))->toThrow(InvalidInputException::class);
    });
});
```

- [ ] **Step 2: Run test — expect failures (classes don't exist yet)**

```bash
# Run only the unit test file
docker run --rm \
  -v "${PWD}/Template:/app" -w /app \
  -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" \
  -e DB_CONNECTION=sqlite -e "DB_DATABASE=:memory:" \
  -e CACHE_STORE=array -e QUEUE_CONNECTION=sync \
  -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false \
  laravel-test:local \
  php -d memory_limit=512M -d display_errors=stderr \
  vendor/bin/pest tests/Unit --no-coverage
```

Expected: FAIL — "Class not found" errors.

- [ ] **Step 3: Create `app/Domain/Program/ProgramId.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\ValueObject;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class ProgramId extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('ProgramId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::uuid4());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 4: Create `app/Domain/Program/ProgramTitle.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramTitle extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty(trim($this->value))) {
            throw new InvalidInputException('Program title cannot be empty.');
        }

        if (mb_strlen($this->value) > 255) {
            throw new InvalidInputException('Program title cannot exceed 255 characters.');
        }
    }
}
```

- [ ] **Step 5: Create `app/Domain/Program/ProgramDescription.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramDescription extends ValueObject
{
    public function __construct(public readonly ?string $value)
    {
        if ($this->value !== null && mb_strlen($this->value) > 2000) {
            throw new InvalidInputException('Program description cannot exceed 2000 characters.');
        }
    }
}
```

- [ ] **Step 6: Create `app/Domain/Program/ProgramDuration.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramDuration extends ValueObject
{
    public function __construct(public readonly int $minutes)
    {
        if ($this->minutes <= 0) {
            throw new InvalidInputException('Program duration must be greater than 0 minutes.');
        }
    }
}
```

- [ ] **Step 7: Create `app/Domain/Program/ProgramGenre.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramGenre extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty(trim($this->value))) {
            throw new InvalidInputException('Program genre cannot be empty.');
        }

        if (mb_strlen($this->value) > 100) {
            throw new InvalidInputException('Program genre cannot exceed 100 characters.');
        }
    }
}
```

- [ ] **Step 8: Run unit tests — expect value object tests to pass**

Run the same command as Step 2. Expected: value object tests PASS, aggregate tests not yet written (none exist yet, so 0 failures from them).

---

## Task 3: Domain aggregate + events

**Files:**
- Create: `app/Domain/Program/Events/ProgramCreated.php`
- Create: `app/Domain/Program/Events/ProgramUpdated.php`
- Create: `app/Domain/Program/Program.php`

- [ ] **Step 1: Add aggregate tests to `tests/Unit/Program/ProgramTest.php`**

Append to the existing file:

```php
use App\Domain\Program\Program;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramGenre;
use App\Domain\Program\Events\ProgramCreated;
use App\Domain\Program\Events\ProgramUpdated;

describe('Program', function (): void {
    it('records a ProgramCreated event on create', function (): void {
        $program = Program::create(
            id: ProgramId::generate(),
            title: new ProgramTitle('Planet Earth III'),
            description: new ProgramDescription('A nature documentary.'),
            duration: new ProgramDuration(60),
            genre: new ProgramGenre('documentary'),
            ownerId: 'owner-1',
        );

        $events = $program->releaseEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(ProgramCreated::class)
            ->and($events[0]->title)->toBe('Planet Earth III');
    });

    it('releases events only once', function (): void {
        $program = Program::create(
            id: ProgramId::generate(),
            title: new ProgramTitle('Sherlock'),
            description: new ProgramDescription(null),
            duration: new ProgramDuration(90),
            genre: new ProgramGenre('drama'),
            ownerId: 'owner-1',
        );

        $program->releaseEvents();

        expect($program->releaseEvents())->toBeEmpty();
    });

    it('does not record events on reconstitute', function (): void {
        $program = Program::reconstitute(
            id: ProgramId::generate(),
            title: new ProgramTitle('The Wire'),
            description: new ProgramDescription(null),
            duration: new ProgramDuration(55),
            genre: new ProgramGenre('drama'),
            ownerId: 'owner-1',
        );

        expect($program->releaseEvents())->toBeEmpty();
    });

    it('records a ProgramUpdated event on update', function (): void {
        $program = Program::create(
            id: ProgramId::generate(),
            title: new ProgramTitle('Old Title'),
            description: new ProgramDescription(null),
            duration: new ProgramDuration(30),
            genre: new ProgramGenre('comedy'),
            ownerId: 'owner-1',
        );

        $program->releaseEvents(); // clear create event

        $program->update(
            title: new ProgramTitle('New Title'),
            description: new ProgramDescription('Updated description.'),
            duration: new ProgramDuration(45),
        );

        $events = $program->releaseEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(ProgramUpdated::class)
            ->and($events[0]->title)->toBe('New Title');
    });
});
```

- [ ] **Step 2: Run tests — expect failures (Program class missing)**

Run unit tests. Expected: FAIL with "Class Program not found".

- [ ] **Step 3: Create `app/Domain/Program/Events/ProgramCreated.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program\Events;

use App\Domain\Program\ProgramId;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final readonly class ProgramCreated implements DomainEvent
{
    public function __construct(
        public readonly ProgramId $programId,
        public readonly string $title,
        public readonly int $durationMinutes,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
```

- [ ] **Step 4: Create `app/Domain/Program/Events/ProgramUpdated.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program\Events;

use App\Domain\Program\ProgramId;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final readonly class ProgramUpdated implements DomainEvent
{
    public function __construct(
        public readonly ProgramId $programId,
        public readonly string $title,
        public readonly int $durationMinutes,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
```

- [ ] **Step 5: Create `app/Domain/Program/Program.php`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Program\Events\ProgramCreated;
use App\Domain\Program\Events\ProgramUpdated;
use App\Domain\Shared\AggregateRoot;
use DateTimeImmutable;

final class Program extends AggregateRoot
{
    private function __construct(
        private readonly ProgramId $id,
        private ProgramTitle $title,
        private ProgramDescription $description,
        private ProgramDuration $duration,
        private ProgramGenre $genre,
        private readonly string $ownerId,
    ) {}

    public static function create(
        ProgramId $id,
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
        ProgramGenre $genre,
        string $ownerId,
    ): self {
        $program = new self($id, $title, $description, $duration, $genre, $ownerId);

        $program->recordEvent(new ProgramCreated(
            programId: $id,
            title: $title->value,
            durationMinutes: $duration->minutes,
            occurredAt: new DateTimeImmutable(),
        ));

        return $program;
    }

    public static function reconstitute(
        ProgramId $id,
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
        ProgramGenre $genre,
        string $ownerId,
    ): self {
        return new self($id, $title, $description, $duration, $genre, $ownerId);
    }

    public function update(
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;

        $this->recordEvent(new ProgramUpdated(
            programId: $this->id,
            title: $title->value,
            durationMinutes: $duration->minutes,
            occurredAt: new DateTimeImmutable(),
        ));
    }

    public function id(): ProgramId { return $this->id; }
    public function title(): ProgramTitle { return $this->title; }
    public function description(): ProgramDescription { return $this->description; }
    public function duration(): ProgramDuration { return $this->duration; }
    public function genre(): ProgramGenre { return $this->genre; }
    public function ownerId(): string { return $this->ownerId; }
}
```

- [ ] **Step 6: Run unit tests — all must pass**

Expected: all unit tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Domain/Program tests/Unit/Program
git commit -m "feat: Program domain — value objects, aggregate, events"
```

---

## Task 4: Application layer — contracts, DTOs, filter

**Files:**
- Create: `app/Application/Contracts/Repositories/ProgramRepositoryInterface.php`
- Create: `app/Application/Contracts/Finders/ProgramFinderInterface.php`
- Create: `app/Application/Program/ProgramDto.php`
- Create: `app/Application/Program/CreateProgramData.php`
- Create: `app/Application/Program/UpdateProgramData.php`
- Create: `app/Application/Program/ProgramFilter.php`

- [ ] **Step 1: Create `app/Application/Contracts/Repositories/ProgramRepositoryInterface.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Program\Program;
use App\Domain\Program\ProgramId;

interface ProgramRepositoryInterface
{
    public function save(Program $program): void;
    public function findById(ProgramId $id): ?Program;
    public function delete(ProgramId $id): void;
}
```

- [ ] **Step 2: Create `app/Application/Contracts/Finders/ProgramFinderInterface.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Contracts\Finders;

use App\Application\Program\ProgramDto;
use App\Application\Program\ProgramFilter;
use App\Domain\Program\ProgramId;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProgramFinderInterface
{
    public function findById(ProgramId $id): ?ProgramDto;
    public function findAll(ProgramFilter $filter): LengthAwarePaginator;
}
```

- [ ] **Step 3: Create `app/Application/Program/ProgramDto.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program;

use DateTimeImmutable;

final readonly class ProgramDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
        public readonly string $genre,
        public readonly string $ownerId,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
```

- [ ] **Step 4: Create `app/Application/Program/CreateProgramData.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program;

use App\Http\Requests\CreateProgramRequest;

final readonly class CreateProgramData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
        public readonly string $genre,
        public readonly string $ownerId,
    ) {}

    public static function fromRequest(CreateProgramRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            durationMinutes: $request->validated('duration_minutes'),
            genre: $request->validated('genre'),
            ownerId: $request->user()->id,
        );
    }
}
```

- [ ] **Step 5: Create `app/Application/Program/UpdateProgramData.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program;

use App\Http\Requests\UpdateProgramRequest;

final readonly class UpdateProgramData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
    ) {}

    public static function fromRequest(UpdateProgramRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            durationMinutes: $request->validated('duration_minutes'),
        );
    }
}
```

- [ ] **Step 6: Create `app/Application/Program/ProgramFilter.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program;

use Illuminate\Http\Request;

final readonly class ProgramFilter
{
    public function __construct(
        public readonly ?string $genre = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            genre: $request->query('genre'),
            perPage: (int) $request->query('per_page', 15),
            page: (int) $request->query('page', 1),
        );
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add app/Application/Program app/Application/Contracts
git commit -m "feat: Program application layer — contracts, DTOs, filter"
```

---

## Task 5: Application layer — actions and queries

**Files:**
- Create: `app/Application/Program/Actions/CreateProgramAction.php`
- Create: `app/Application/Program/Actions/UpdateProgramAction.php`
- Create: `app/Application/Program/Actions/DeleteProgramAction.php`
- Create: `app/Application/Program/Queries/GetProgramByIdQuery.php`
- Create: `app/Application/Program/Queries/GetProgramsQuery.php`

- [ ] **Step 1: Create `app/Application/Program/Actions/CreateProgramAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Application\Program\CreateProgramData;
use App\Domain\Program\Program;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramGenre;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;
use Illuminate\Support\Facades\DB;

final class CreateProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(CreateProgramData $data): ProgramId
    {
        return DB::transaction(function () use ($data): ProgramId {
            $program = Program::create(
                id: ProgramId::generate(),
                title: new ProgramTitle($data->title),
                description: new ProgramDescription($data->description),
                duration: new ProgramDuration($data->durationMinutes),
                genre: new ProgramGenre($data->genre),
                ownerId: $data->ownerId,
            );

            $this->programRepository->save($program);

            foreach ($program->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }

            return $program->id();
        });
    }
}
```

- [ ] **Step 2: Create `app/Application/Program/Actions/UpdateProgramAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Application\Program\UpdateProgramData;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class UpdateProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(ProgramId $id, UpdateProgramData $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $program = $this->programRepository->findById($id);

            if ($program === null) {
                throw new ResourceNotFoundException("Program {$id} not found.");
            }

            $program->update(
                title: new ProgramTitle($data->title),
                description: new ProgramDescription($data->description),
                duration: new ProgramDuration($data->durationMinutes),
            );

            $this->programRepository->save($program);

            foreach ($program->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }
        });
    }
}
```

- [ ] **Step 3: Create `app/Application/Program/Actions/DeleteProgramAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Domain\Program\ProgramId;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class DeleteProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(ProgramId $id): void
    {
        DB::transaction(function () use ($id): void {
            $program = $this->programRepository->findById($id);

            if ($program === null) {
                throw new ResourceNotFoundException("Program {$id} not found.");
            }

            $this->programRepository->delete($id);
        });
    }
}
```

- [ ] **Step 4: Create `app/Application/Program/Queries/GetProgramByIdQuery.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program\Queries;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramDto;
use App\Domain\Program\ProgramId;

final class GetProgramByIdQuery
{
    public function __construct(
        private readonly ProgramFinderInterface $finder,
    ) {}

    public function handle(ProgramId $id): ?ProgramDto
    {
        return $this->finder->findById($id);
    }
}
```

- [ ] **Step 5: Create `app/Application/Program/Queries/GetProgramsQuery.php`**

```php
<?php

declare(strict_types=1);

namespace App\Application\Program\Queries;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramFilter;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetProgramsQuery
{
    public function __construct(
        private readonly ProgramFinderInterface $finder,
    ) {}

    public function handle(ProgramFilter $filter): LengthAwarePaginator
    {
        return $this->finder->findAll($filter);
    }
}
```

- [ ] **Step 6: Run unit tests — must still pass**

Same docker run command. Expected: all unit tests PASS (Application layer has no unit tests yet — just verify nothing broke).

- [ ] **Step 7: Commit**

```bash
git add app/Application/Program
git commit -m "feat: Program application layer — actions and queries"
```

---

## Task 6: Infrastructure layer

**Files:**
- Create: `app/Infrastructure/Persistence/Program/ProgramEloquentModel.php`
- Create: `app/Infrastructure/Persistence/Program/ProgramRepository.php`
- Create: `app/Infrastructure/Persistence/Program/ProgramFinder.php`
- Modify: `app/Infrastructure/Providers/InfrastructureServiceProvider.php`

- [ ] **Step 1: Create `app/Infrastructure/Persistence/Program/ProgramEloquentModel.php`**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Infrastructure\Persistence\Scopes\OwnerScope;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProgramEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'programs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'description', 'duration_minutes', 'genre', 'owner_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(app(OwnerScope::class));
    }

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
```

- [ ] **Step 2: Create `app/Infrastructure/Persistence/Program/ProgramRepository.php`**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Domain\Program\Program;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramGenre;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;

final class ProgramRepository implements ProgramRepositoryInterface
{
    public function save(Program $program): void
    {
        ProgramEloquentModel::withoutGlobalScopes()->updateOrCreate(
            ['id' => (string) $program->id()],
            [
                'title' => $program->title()->value,
                'description' => $program->description()->value,
                'duration_minutes' => $program->duration()->minutes,
                'genre' => $program->genre()->value,
                'owner_id' => $program->ownerId(),
            ]
        );
    }

    public function findById(ProgramId $id): ?Program
    {
        $model = ProgramEloquentModel::withoutGlobalScopes()->find((string) $id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function delete(ProgramId $id): void
    {
        ProgramEloquentModel::withoutGlobalScopes()->destroy((string) $id);
    }

    private function toDomain(ProgramEloquentModel $model): Program
    {
        return Program::reconstitute(
            id: ProgramId::fromString($model->id),
            title: new ProgramTitle($model->title),
            description: new ProgramDescription($model->description),
            duration: new ProgramDuration($model->duration_minutes),
            genre: new ProgramGenre($model->genre),
            ownerId: $model->owner_id,
        );
    }
}
```

- [ ] **Step 3: Create `app/Infrastructure/Persistence/Program/ProgramFinder.php`**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramDto;
use App\Application\Program\ProgramFilter;
use App\Domain\Program\ProgramId;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProgramFinder implements ProgramFinderInterface
{
    public function findById(ProgramId $id): ?ProgramDto
    {
        $model = ProgramEloquentModel::find((string) $id);

        return $model !== null ? $this->toDto($model) : null;
    }

    public function findAll(ProgramFilter $filter): LengthAwarePaginator
    {
        $query = ProgramEloquentModel::query();

        if ($filter->genre !== null) {
            $query->where('genre', $filter->genre);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    private function toDto(ProgramEloquentModel $model): ProgramDto
    {
        return new ProgramDto(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            durationMinutes: $model->duration_minutes,
            genre: $model->genre,
            ownerId: $model->owner_id,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
```

- [ ] **Step 4: Replace `app/Infrastructure/Providers/InfrastructureServiceProvider.php`**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Infrastructure\Persistence\Program\ProgramFinder;
use App\Infrastructure\Persistence\Program\ProgramRepository;
use Illuminate\Support\ServiceProvider;

final class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProgramRepositoryInterface::class,
            ProgramRepository::class,
        );

        $this->app->bind(
            ProgramFinderInterface::class,
            ProgramFinder::class,
        );
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Infrastructure
git commit -m "feat: Program infrastructure layer — Eloquent model, repository, finder"
```

---

## Task 7: Database — migration, factory, seeder

**Files:**
- Create: `database/migrations/2026_01_01_000001_create_programs_table.php`
- Create: `database/factories/ProgramFactory.php`
- Modify: `database/seeders/DevDataSeeder.php`

- [ ] **Step 1: Create `database/migrations/2026_01_01_000001_create_programs_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->string('genre');
            $table->string('owner_id');
            $table->timestamps();

            $table->index('owner_id');
            $table->index('genre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
```

- [ ] **Step 2: Create `database/factories/ProgramFactory.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProgramEloquentModel>
 */
final class ProgramFactory extends Factory
{
    protected $model = ProgramEloquentModel::class;

    private array $genres = ['drama', 'comedy', 'documentary', 'thriller', 'animation'];

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'genre' => $this->faker->randomElement($this->genres),
            'owner_id' => (string) Str::uuid(),
        ];
    }

    public function documentary(): self
    {
        return $this->state(['genre' => 'documentary']);
    }

    public function ownedBy(string $ownerId): self
    {
        return $this->state(['owner_id' => $ownerId]);
    }
}
```

- [ ] **Step 3: Replace `database/seeders/DevDataSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = UserEloquentModel::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        ProgramEloquentModel::factory()
            ->count(10)
            ->ownedBy($user->id)
            ->create();
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database
git commit -m "feat: Program migration, factory, seeder with test user"
```

---

## Task 8: HTTP auth endpoint

**Files:**
- Create: `app/Http/Controllers/AuthController.php`
- Create: `app/Http/Requests/LoginRequest.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Create `app/Http/Requests/LoginRequest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 2: Create `app/Http/Controllers/AuthController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = UserEloquentModel::where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
```

- [ ] **Step 3: Update `routes/api.php` with login route**

Replace the file contents:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('programs', ProgramController::class);
});
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AuthController.php app/Http/Requests/LoginRequest.php routes/api.php
git commit -m "feat: POST /api/login — issues Sanctum personal access token"
```

---

## Task 9: HTTP programs layer

**Files:**
- Create: `app/Http/Requests/CreateProgramRequest.php`
- Create: `app/Http/Requests/UpdateProgramRequest.php`
- Create: `app/Http/Resources/ProgramResource.php`
- Create: `app/Http/Controllers/ProgramController.php`

- [ ] **Step 1: Create `app/Http/Requests/CreateProgramRequest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'genre' => ['required', 'string', 'max:100'],
        ];
    }
}
```

- [ ] **Step 2: Create `app/Http/Requests/UpdateProgramRequest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = ProgramEloquentModel::withoutGlobalScopes()->find($this->route('program'));

        return $program !== null && $this->user()?->getAuthIdentifier() === $program->owner_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

- [ ] **Step 3: Create `app/Http/Resources/ProgramResource.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Application\Program\ProgramDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProgramDto $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'title' => $dto->title,
            'description' => $dto->description,
            'duration_minutes' => $dto->durationMinutes,
            'genre' => $dto->genre,
            'created_at' => $dto->createdAt->format('c'),
        ];
    }
}
```

- [ ] **Step 4: Create `app/Http/Controllers/ProgramController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Program\Actions\CreateProgramAction;
use App\Application\Program\Actions\DeleteProgramAction;
use App\Application\Program\Actions\UpdateProgramAction;
use App\Application\Program\CreateProgramData;
use App\Application\Program\ProgramFilter;
use App\Application\Program\Queries\GetProgramByIdQuery;
use App\Application\Program\Queries\GetProgramsQuery;
use App\Application\Program\UpdateProgramData;
use App\Domain\Program\ProgramId;
use App\Http\Requests\CreateProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProgramController extends Controller
{
    public function index(Request $request, GetProgramsQuery $query): AnonymousResourceCollection
    {
        return ProgramResource::collection(
            $query->handle(ProgramFilter::fromRequest($request))
        );
    }

    public function show(string $program, GetProgramByIdQuery $query): ProgramResource|JsonResponse
    {
        $dto = $query->handle(ProgramId::fromString($program));

        if ($dto === null) {
            return response()->json(['message' => 'Program not found.'], 404);
        }

        return new ProgramResource($dto);
    }

    public function store(CreateProgramRequest $request, CreateProgramAction $action): JsonResponse
    {
        $id = $action->handle(CreateProgramData::fromRequest($request));

        return response()->json(['id' => (string) $id], 201);
    }

    public function update(string $program, UpdateProgramRequest $request, UpdateProgramAction $action): JsonResponse
    {
        $action->handle(ProgramId::fromString($program), UpdateProgramData::fromRequest($request));

        return response()->json(null, 204);
    }

    public function destroy(string $program, DeleteProgramAction $action): JsonResponse
    {
        $action->handle(ProgramId::fromString($program));

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http
git commit -m "feat: Program HTTP layer — controller, requests, resource"
```

---

## Task 10: Feature tests + verify full Pest suite

**Files:**
- Create: `tests/Feature/Program/CreateProgramTest.php`

- [ ] **Step 1: Write the feature test**

Create `tests/Feature/Program/CreateProgramTest.php`:

```php
<?php

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/programs', function (): void {
    it('creates a program and returns 201 with id', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'Planet Earth III',
                'description' => 'A stunning nature documentary.',
                'duration_minutes' => 60,
                'genre' => 'documentary',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id']);

        expect(ProgramEloquentModel::withoutGlobalScopes()->count())->toBe(1);
    });

    it('returns 422 when title is missing', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'duration_minutes' => 60,
                'genre' => 'documentary',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('returns 422 when duration_minutes is zero', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'Test',
                'duration_minutes' => 0,
                'genre' => 'drama',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_minutes']);
    });

    it('returns 201 when description is omitted', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'The Wire',
                'duration_minutes' => 55,
                'genre' => 'drama',
            ])
            ->assertStatus(201);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->postJson('/api/programs', ['title' => 'Test'])->assertStatus(401);
    });
});
```

- [ ] **Step 2: Run the full Pest suite — all must pass**

```bash
docker run --rm \
  -v "${PWD}/Template:/app" -w /app \
  -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" \
  -e DB_CONNECTION=sqlite -e "DB_DATABASE=:memory:" \
  -e CACHE_STORE=array -e QUEUE_CONNECTION=sync \
  -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false \
  laravel-test:local \
  php -d memory_limit=512M -d display_errors=stderr \
  vendor/bin/pest --no-coverage
```

Expected output:
```
PASS  Tests\Unit\Program\ProgramTest           (9 tests)
PASS  Tests\Feature\Program\CreateProgramTest  (5 tests)
PASS  Tests\Architecture\ApplicationLayerTest  (2 tests)
PASS  Tests\Architecture\DomainLayerTest       (2 tests)
PASS  Tests\Architecture\NamingConventionTest  (2 tests)

Tests: 20 passed
```

If any test fails, fix it before continuing.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Program
git commit -m "test: Program feature tests — create, validate, auth"
```

---

## Task 11: Playwright E2E test

**Files:**
- Modify: `client/playwright.config.ts`
- Modify: `client/package.json`
- Create: `client/playwright/api/programs.spec.ts`

- [ ] **Step 1: Update `client/playwright.config.ts`**

Replace the file contents:

```typescript
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
```

- [ ] **Step 2: Add `e2e:api` script to `client/package.json`**

Add one entry to `"scripts"`:

```json
"e2e:api": "playwright test --project=api"
```

The full scripts block becomes:

```json
"scripts": {
  "start": "ng serve",
  "build": "ng build",
  "test": "vitest run",
  "test:watch": "vitest",
  "e2e:api": "playwright test --project=api",
  "e2e:cypress": "cypress run",
  "e2e:playwright": "playwright test",
  "openapi": "openapi-typescript http://localhost:8000/api/documentation.json -o src/app/api/types.ts"
}
```

- [ ] **Step 3: Create `client/playwright/api/programs.spec.ts`**

```typescript
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
```

- [ ] **Step 4: Run the E2E test**

The E2E test requires a live Laravel server. Run these in separate terminals from `Template/`:

```bash
# Terminal 1 — fresh DB with test user seeded
php artisan migrate:fresh --seed

# Terminal 2 — API server
php artisan serve
```

Then run the Playwright test from `Template/client/`:

```bash
cd client
pnpm run e2e:api
```

Expected: all 8 tests PASS (the test order matters — `programId` from test 1 is used by tests 2–7).

If `php` is not available locally, use Docker:

```bash
# Terminal 1 — migrate + seed using PHP 8.4 image
docker run --rm \
  -v "${PWD}/Template:/app" -w /app \
  --network dev_default \
  -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" \
  -e DB_CONNECTION=pgsql -e DB_HOST=dev-postgres-1 \
  -e DB_PORT=5432 -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel -e DB_PASSWORD=secret \
  laravel-test:local \
  php -d memory_limit=512M artisan migrate:fresh --seed --force

# Terminal 2 — serve using the same image, mapping port 8000
docker run --rm \
  -v "${PWD}/Template:/app" -w /app \
  -p 8000:8000 \
  --network dev_default \
  -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" \
  -e DB_CONNECTION=pgsql -e DB_HOST=dev-postgres-1 \
  -e DB_PORT=5432 -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel -e DB_PASSWORD=secret \
  laravel-test:local \
  php -d memory_limit=512M artisan serve --host=0.0.0.0 --port=8000
```

- [ ] **Step 5: Commit**

```bash
git add client/playwright.config.ts client/package.json client/playwright
git commit -m "test(e2e): Playwright API E2E — full CRUD cycle for Programs"
```

---

## Task 12: Final push

- [ ] **Step 1: Push to GitHub**

```bash
git push
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Covered by |
|---|---|
| Delete all Product files | Task 1 |
| ProgramId, ProgramTitle, ProgramDescription, ProgramDuration, ProgramGenre | Task 2 |
| Program aggregate + create/reconstitute/update | Task 3 |
| ProgramCreated, ProgramUpdated events | Task 3 |
| ProgramRepositoryInterface, ProgramFinderInterface | Task 4 |
| ProgramDto (7 fields incl. description) | Task 4 |
| CreateProgramData, UpdateProgramData, ProgramFilter (genre filter) | Task 4 |
| CreateProgramAction, UpdateProgramAction, DeleteProgramAction | Task 5 |
| GetProgramByIdQuery, GetProgramsQuery | Task 5 |
| ProgramEloquentModel (UUID, OwnerScope, newFactory) | Task 6 |
| ProgramRepository (withoutGlobalScopes, toDomain via reconstitute) | Task 6 |
| ProgramFinder (genre filter, paginate) | Task 6 |
| InfrastructureServiceProvider bindings updated | Task 6 |
| programs migration (description nullable, genre indexed) | Task 7 |
| ProgramFactory (5 genres, ownedBy state) | Task 7 |
| DevDataSeeder seeds test user + 10 programs | Task 7 |
| AuthController + LoginRequest + POST /api/login | Task 8 |
| 401 on wrong password | Task 8 |
| ProgramController (5 methods, thin) | Task 9 |
| CreateProgramRequest, UpdateProgramRequest (owner check) | Task 9 |
| ProgramResource (wraps ProgramDto) | Task 9 |
| Unit tests: value objects + aggregate | Tasks 2–3 |
| Feature tests: 201, 422 (missing title, zero duration), 401 | Task 10 |
| Architecture tests: unchanged | ✓ (not touched) |
| client/playwright.config.ts: api project + chromium unchanged | Task 11 |
| package.json: e2e:api script | Task 11 |
| programs.spec.ts: 8 tests covering full CRUD + auth | Task 11 |
