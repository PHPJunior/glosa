<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Queries\GetPublicTranslationsQuery;

class PublicTranslationController extends Controller
{
    protected GetPublicTranslationsQuery $query;

    public function __construct(GetPublicTranslationsQuery $query)
    {
        $this->query = $query;
    }

    public function __invoke($locale)
    {
        return response()->json($this->query->get($locale));
    }
}
