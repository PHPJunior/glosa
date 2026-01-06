<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Actions\CreateKeyAction;
use PhpJunior\Glosa\Actions\DeleteKeyAction;
use PhpJunior\Glosa\Actions\UpdateKeyAction;
use PhpJunior\Glosa\Http\Resources\TranslationKeyResource;
use PhpJunior\Glosa\Queries\GetTranslationGroupsQuery;

class TranslationKeyController extends Controller
{
    protected GetTranslationGroupsQuery $getTranslationGroupsQuery;
    protected CreateKeyAction $createKeyAction;
    protected UpdateKeyAction $updateKeyAction;
    protected DeleteKeyAction $deleteKeyAction;

    public function __construct(
        GetTranslationGroupsQuery $getTranslationGroupsQuery,
        CreateKeyAction $createKeyAction,
        UpdateKeyAction $updateKeyAction,
        DeleteKeyAction $deleteKeyAction
    ) {
        $this->getTranslationGroupsQuery = $getTranslationGroupsQuery;
        $this->createKeyAction = $createKeyAction;
        $this->updateKeyAction = $updateKeyAction;
        $this->deleteKeyAction = $deleteKeyAction;
    }

    /**
     * @return mixed
     */
    public function groups()
    {
        return $this->getTranslationGroupsQuery->get();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group' => 'required|string',
            'key' => 'required|string',
        ]);

        $key = $this->createKeyAction->execute($validated);

        return response()->json(['status' => 'success', 'key' => new TranslationKeyResource($key)]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'group' => 'required|string',
            'key' => 'required|string',
        ]);

        $key = $this->updateKeyAction->execute($id, $validated);

        return response()->json(['status' => 'success', 'key' => new TranslationKeyResource($key)]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $this->deleteKeyAction->execute($id);

        return response()->json(['status' => 'success']);
    }
}
