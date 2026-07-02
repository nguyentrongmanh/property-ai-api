<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @template TModel of Model
 */
interface CrudServiceInterface
{
    /**
     * List records matching the given filters, paginated.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TModel>
     */
    public function filter(array $filters): LengthAwarePaginator;

    /**
     * Get a single record by its ID.
     *
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function detail(string $id): Model;

    /**
     * Create a new record with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * Update the record with the given ID and return it.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function edit(string $id, array $attributes): Model;

    /**
     * Delete the record with the given ID.
     *
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void;
}
