<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons sesuai api-spec.md §1: `{data}` atau `{data, meta}`.
 */
trait RespondsWithApi
{
    protected function item(JsonResource $resource, int $status = 200): JsonResponse
    {
        return $resource->response()->setStatusCode($status);
    }

    /**
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function forbidden(string $message = 'Akses ditolak, anda tidak memiliki akses untuk data ini.'): JsonResponse
    {
        return $this->message($message, 403);
    }

    /** `per_page` default 10, maksimal 100. */
    protected function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->query('per_page', 10)));
    }

    /** Escape wildcard LIKE supaya `%` / `_` dari pengguna dicari apa adanya. */
    protected function like(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
