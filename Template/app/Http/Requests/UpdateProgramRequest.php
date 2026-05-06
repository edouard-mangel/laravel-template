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
