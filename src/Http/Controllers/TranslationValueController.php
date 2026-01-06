<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Actions\UpdateTranslationValueAction;

class TranslationValueController extends Controller
{
    protected UpdateTranslationValueAction $updateTranslationValueAction;

    public function __construct(UpdateTranslationValueAction $updateTranslationValueAction)
    {
        $this->updateTranslationValueAction = $updateTranslationValueAction;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key_id' => 'required|exists:glosa_keys,id',
            'locale' => 'required|string|exists:glosa_locales,code',
            'value' => 'nullable|string',
        ]);

        $this->updateTranslationValueAction->execute([
            'key_id' => $validated['key_id'],
            'locale' => $validated['locale'],
            'value' => $validated['value']
        ]);

        return response()->json(['status' => 'success']);
    }
}
