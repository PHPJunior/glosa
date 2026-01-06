<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Actions\CreateLocaleAction;
use PhpJunior\Glosa\Actions\DeleteLocaleAction;
use PhpJunior\Glosa\Actions\UpdateLocaleAction;
use PhpJunior\Glosa\Http\Resources\LocaleResource;

class LocaleController extends Controller
{
    protected CreateLocaleAction $createLocaleAction;
    protected UpdateLocaleAction $updateLocaleAction;
    protected DeleteLocaleAction $deleteLocaleAction;

    public function __construct(
        CreateLocaleAction $createLocaleAction,
        UpdateLocaleAction $updateLocaleAction,
        DeleteLocaleAction $deleteLocaleAction
    ) {
        $this->createLocaleAction = $createLocaleAction;
        $this->updateLocaleAction = $updateLocaleAction;
        $this->deleteLocaleAction = $deleteLocaleAction;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|max:10|unique:glosa_locales,code',
            'is_default' => 'boolean'
        ]);

        // Map 'locale' input to 'code' and 'name' for the action
        $data = [
            'code' => $validated['locale'],
            'name' => $validated['locale'],
            'is_default' => $request->boolean('is_default')
        ];

        $locale = $this->createLocaleAction->execute($data);

        return response()->json(['status' => 'success', 'locale' => new LocaleResource($locale)]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:glosa_locales,code,' . $id,
            'is_default' => 'boolean'
        ]);

        $data = [
            'code' => $validated['code'],
            'name' => $validated['code'], // Assuming name follows code for now as per original
            'is_default' => $request->boolean('is_default')
        ];

        $locale = $this->updateLocaleAction->execute($id, $data);

        return response()->json(['status' => 'success', 'locale' => new LocaleResource($locale)]);
    }

    public function destroy($id)
    {
        $this->deleteLocaleAction->execute($id);

        return response()->json(['status' => 'success']);
    }
}
