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
        $itemsCollection = collect($searchArray);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $itemsCollection->slice(($currentPage - 1) * $noItemsPerPage, $noItemsPerPage)->all();
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemsCollection), $noItemsPerPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(), // This will ensure all query parameters are kept
        ]);
        $paginatedItems->setPath($request->url());
        $paginatedItems->appends($request->query());

        return $paginatedItems;
    }
}