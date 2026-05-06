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
