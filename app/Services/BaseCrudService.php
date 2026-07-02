<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Services\Contracts\CrudServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Default CRUD behavior delegating to a repository. Concrete services
 * extend this and override only what is specific to their domain.
 *
 * @template TModel of Model
 *
 * @implements CrudServiceInterface<TModel>
 */
abstract class BaseCrudService implements CrudServiceInterface
{
    protected const DEFAULT_PER_PAGE = 15;

    /**
     * @param  RepositoryInterface<TModel>  $repository
     */
    public function __construct(
        protected readonly RepositoryInterface $repository,
    ) {}

    public function filter(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? static::DEFAULT_PER_PAGE);

        return $this->repository->filter($filters, $perPage);
    }

    public function detail(string $id): Model
    {
        return $this->repository->detail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->repository->create($attributes);
    }

    public function edit(string $id, array $attributes): Model
    {
        return $this->repository->edit($id, $attributes);
    }

    public function delete(string $id): void
    {
        $this->repository->delete($id);
    }
}
