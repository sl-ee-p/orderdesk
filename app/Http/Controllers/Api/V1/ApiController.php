<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
