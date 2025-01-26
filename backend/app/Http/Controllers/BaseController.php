<?php

namespace App\Http\Controllers;

use App\Services\ManageFilesService;
use App\Services\ResponsesService;

abstract class BaseController
{
    protected $responsesService, $manageFilesService;

    public function __construct(ResponsesService $responsesService, ManageFilesService $manageFilesService)
    {
        $this->responsesService = $responsesService;
        $this->manageFilesService = $manageFilesService;
    }

    protected $limit_pagination = 20;
    protected $columns_select = ['id'];
    protected $columns_search = ['id'];
    
    protected function applyFilters($query, $filters)
    {
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $parts = explode('|', $filter);
                if (count($parts) === 2) {
                    [$column, $value] = $parts;
                    $query->where($column, 'LIKE', '%' . $value . '%');
                }
            }
        }
    }

    protected function applySearch($query, $searchTerm, $columns)
    {
        foreach ($columns as $column) {
            $query->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
        }
    }

    protected function applyOrderBy($query, $orderBy)
    {
        if ($orderBy) {
            $orderBy = is_array($orderBy) ? $orderBy : [$orderBy];
            foreach ($orderBy as $order) {
                $parts = explode('|', $order);
                if (count($parts) === 2) {
                    [$column, $direction] = $parts;
                    $query->orderBy($column, strtolower($direction) === 'DESC' ? 'DESC' : 'ASC');
                }
            }
        } else {
            $query->orderBy('id', 'DESC');
        }
    }

    protected function calculatePagination($total, $limit, $page)
    {
        if ($total == 0) {
            return [0, 0];
        }
        $from = ($page - 1) * $limit + 1;
        $to = min($page * $limit, $total);
        return [$from, $to];
    }
}
