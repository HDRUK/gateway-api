<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginateFromArray
{
    /**
     * Creates a paginated instance from an array of items.
     *
     * @param Request $request
     * @param array $searchArray
     * @param integer $noItemsPerPage
     * @return LengthAwarePaginator
     */
    private function paginateArray(Request $request, array $searchArray, int $noItemsPerPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $noItemsPerPage;
        $currentPageItems = array_slice($searchArray, $offset, $noItemsPerPage, false);
        $paginatedItems = new LengthAwarePaginator(
            $currentPageItems,
            count($searchArray),
            $noItemsPerPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $paginatedItems->setPath($request->url());
        $paginatedItems->appends($request->query());

        return $paginatedItems;
    }
}
