<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

abstract class ApiController extends Controller
{
    protected const MAX_PER_PAGE = 100;

    /**
     * Page size from ?per_page=, kept between 1 and MAX_PER_PAGE.
     * A non-numeric value is a 422, not a silent one-row page.
     */
    protected function perPage(Request $request): int
    {
        $perPage = $request->validate(['per_page' => ['nullable', 'integer']])['per_page'] ?? 15;

        return max(1, min(self::MAX_PER_PAGE, (int) $perPage));
    }

    /**
     * Validated ?status= and ?customer_id= filters for quote and order lists.
     * An unknown status or a non-numeric customer id is a 422, not an empty page.
     *
     * @param  class-string<BackedEnum>  $statusEnum
     * @return array{status?: ?string, customer_id?: ?int}
     */
    protected function documentFilters(Request $request, string $statusEnum): array
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum($statusEnum)],
            'customer_id' => ['nullable', 'integer'],
        ]);

        if (isset($filters['customer_id'])) {
            $filters['customer_id'] = (int) $filters['customer_id'];
        }

        return $filters;
    }
}
