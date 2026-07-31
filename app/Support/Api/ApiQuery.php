<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiQuery
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<TModel>
     */
    public static function applyPagination(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, string>  $whitelist
     * @return Builder<TModel>
     */
    public static function applySort(Builder $query, ?string $sort, array $whitelist, string $default = '-id'): Builder
    {
        $sort = $sort ?: $default;
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $key = ltrim($sort, '-');

        if (! array_key_exists($key, $whitelist)) {
            $direction = str_starts_with($default, '-') ? 'desc' : 'asc';
            $key = ltrim($default, '-');
        }

        return $query->orderBy($whitelist[$key], $direction);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $columns
     * @param  (callable(Builder<TModel>, string): void)|null  $additional
     * @return Builder<TModel>
     */
    public static function applySearch(Builder $query, ?string $search, array $columns, ?callable $additional = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $like = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($columns, $like, $additional): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($column, 'like', $like);
            }

            if ($additional !== null) {
                $additional($query, $like);
            }
        });
    }
}
