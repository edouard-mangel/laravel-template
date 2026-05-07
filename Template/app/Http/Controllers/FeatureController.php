<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Pennant\Feature;

final class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Feature::all());
    }
}
