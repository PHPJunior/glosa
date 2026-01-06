<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Queries\GetGroupedTranslationsQuery;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected GetGroupedTranslationsQuery $query;

    public function __construct(GetGroupedTranslationsQuery $query)
    {
        $this->query = $query;
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        return view('glosa::index');
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function grouped(Request $request)
    {
        return $this->query->get(
            $request->only(['limit', 'search', 'group', 'missing_locale'])
        );
    }
}
