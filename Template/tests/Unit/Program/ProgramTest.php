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
