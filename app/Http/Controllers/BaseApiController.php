<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;

    protected $limit = 10;

    public function __construct()
    {
        $this->middleware('auth:api');
    }
}
