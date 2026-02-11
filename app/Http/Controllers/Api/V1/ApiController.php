<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
}
