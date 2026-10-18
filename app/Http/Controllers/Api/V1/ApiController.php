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
     */
    protected function perPage(Request $request): int
    {
        return max(1, min(self::MAX_PER_PAGE, $request->integer('per_page', 15)));
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
