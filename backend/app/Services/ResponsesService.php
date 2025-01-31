<?php

namespace App\Services;

class ResponsesService
{
    public function success($status = 200, $message = 'Success', $data = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function error($status = 400, $message = 'Error', $data = null)
    {
        return response()->json([
            'status' => $status,
            'error' => $message,
            'data' => $data
        ], $status);
    }

    public function pagination($status = 200, $message = 'Success', $data = null, $from = 0, $to = 0, $page = 1, $limit = 10, $total = 0)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'from' => $from,
                'to' => $to,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]
        ], $status);
    }
}
