<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;

class ManageFilesService
{
    protected $responsesService;

    public function __construct(ResponsesService $responsesService)
    {
        $this->responsesService = $responsesService;
    }

    
}
