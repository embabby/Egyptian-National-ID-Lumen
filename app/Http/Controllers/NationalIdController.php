<?php

namespace App\Http\Controllers;

use App\Services\EgyptianNationalIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NationalIdController extends Controller
{
    public function __construct(
        private EgyptianNationalIdService $nationalIdService
    ) {
    }

    /**
     * Validate and extract data from an Egyptian national ID.
     *
     * POST /api/v1/national-id/validate
     * Body: { "national_id": "29001011234567" }
     * or
     * GET /api/v1/national-id/validate?national_id=29001011234567
     */
    public function validateId(Request $request): JsonResponse
    {
        $raw = $request->input('national_id') ?? $request->query('national_id');
        if ($raw === null || $raw === '') {
            throw ValidationException::withMessages([
                'national_id' => ['The national ID field is required.'],
            ]);
        }

        $id = preg_replace('/\s+/', '', (string) $raw);
        if (strlen($id) !== 14 || !ctype_digit($id)) {
            throw ValidationException::withMessages([
                'national_id' => ['The national ID must be exactly 14 digits.'],
            ]);
        }

        $data = $this->nationalIdService->extract($id);

        return response()->json([
            'valid' => $data['valid'],
            'national_id' => $id,
            'data' => $data['valid'] ? [
                'birth_date' => $data['birth_date'],
                'birth_year' => $data['birth_year'],
                'birth_month' => $data['birth_month'],
                'birth_day' => $data['birth_day'],
                'gender' => $data['gender'],
                'governorate' => [
                    'code' => $data['governorate_code'],
                    'name' => $data['governorate_name'],
                ],
            ] : null,
        ]);
    }
}
