<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\CrudServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base controller. Children pass their concrete service to the
 * constructor and set the resource class-string.
 */
abstract class BaseApiController extends Controller
{
    /** @var class-string<JsonResource> */
    protected string $resource = JsonResource::class;

    protected string $emptyListMessage = 'No records matched the given filters.';

    public function __construct(
        protected readonly CrudServiceInterface $service,
    ) {}

    /**
     * Respond with a paginated listing, or an explicit message when
     * nothing matched instead of an unexplained empty list.
     *
     * @param  LengthAwarePaginator<int, Model>  $records
     */
    protected function respondList(LengthAwarePaginator $records): JsonResponse|AnonymousResourceCollection
    {
        if ($records->isEmpty()) {
            return $this->respondEmptyList($this->emptyListMessage);
        }

        return $this->resource::collection($records);
    }

    /**
     * Respond with a single record wrapped in the configured resource class.
     */
    protected function respondItem(Model $record): JsonResource
    {
        return new $this->resource($record);
    }

    /**
     * Respond with data and an optional message.
     */
    protected function respondSuccess(mixed $data = null, ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'data' => $data,
        ], fn (mixed $value) => $value !== null), $status);
    }

    /**
     * Respond with a newly created resource.
     */
    protected function respondCreated(JsonResource $resource, ?string $message = null): JsonResponse
    {
        return $resource
            ->additional(array_filter(['message' => $message]))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Respond with an explicit message when a listing has no matches.
     */
    protected function respondEmptyList(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [],
        ]);
    }

    /**
     * Respond with an error message.
     */
    protected function respondError(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
