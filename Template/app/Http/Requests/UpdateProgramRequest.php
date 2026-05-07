<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = ProgramEloquentModel::withoutGlobalScopes()->where('id', $this->route('program'))->first();

        return $program !== null && $this->user()?->getAuthIdentifier() === $program->owner_id;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ];
    }
}
