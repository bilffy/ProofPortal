<?php

namespace App\Services;
use Illuminate\Database\Eloquent\Builder;

class CollectionQueryService
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param string|array $column
     * @param ?string $term
     * 
     * @return self
     */
    public function search(string|array $column, ?string $term = ""): self
    {
        if (is_array($column)) {
            $this->query->where(function ($query) use ($column, $term) {
                foreach ($column as $col) {
                    $query->orWhere($col, 'like', "%{$term}%");
                }
            });
        } else {
            $this->query->where($column, 'like', "%{$term}%");
        }
        
        return $this;
    }

    /**
     * @param string|array $column
     * @param ?string $direction
     * 
     * @return self
     */
    public function sort(string|array $column, ?string $direction = 'asc'): self
    {
        if (is_array($column)) {
            foreach ($column as $col) {
                $this->query->orderBy($col, $direction);
            }
        } else {
            $this->query->orderBy($column, $direction);
        }
        return $this;
    }

    /**
     * @param array $filters
     * 
     * @return self
     */
    public function filter(array $filters = []): self
    {
        foreach ($filters as $column => $values) {
            if (!empty($values)) {
                $this->query->where(function ($query) use ($column, $values) {
                    foreach ($values as $term) {
                        $query->orWhere($column, 'like', "%{$term}%");
                    }
                });
            }
        }
        return $this;
    }

    public function get()
    {
        return $this->query->get();
    }

    public function paginate(int $perPage = 20, bool $withQuery = true)
    {
        $paginate = $this->query->paginate($perPage);
        if ($withQuery) {
            $paginate->withQueryString();
        }
        return $paginate;
    }
}