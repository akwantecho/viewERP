<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class FallbackController extends Controller
{
    public function __invoke(): void
    {
        abort(Response::HTTP_NOT_FOUND);
    }
}
