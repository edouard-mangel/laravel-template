<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Infrastructure\Persistence\Product\ProductEloquentModel;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = ProductEloquentModel::withoutGlobalScopes()->find($this->route('product'));

        return $product !== null && $this->user()?->getAuthIdentifier() === $product->owner_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price_in_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
