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
