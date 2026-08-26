<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use PhpJunior\Glosa\Actions\ImportTranslationsAction;
use PhpJunior\Glosa\Queries\ExportTranslationsQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    protected ImportTranslationsAction $importTranslationsAction;

    protected ExportTranslationsQuery $exportTranslationsQuery;

    public function __construct(
        ImportTranslationsAction $importTranslationsAction,
        ExportTranslationsQuery $exportTranslationsQuery
    ) {
        $this->importTranslationsAction = $importTranslationsAction;
        $this->exportTranslationsQuery = $exportTranslationsQuery;
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|exists:glosa_locales,code',
            'file' => 'required|file|mimetypes:application/json,text/plain',
            'overwrite' => 'nullable|boolean',
        ]);

        try {
            $count = $this->importTranslationsAction->execute(
                $validated['locale'],
                $request->file('file')->getRealPath(),
                $request->boolean('overwrite', true)
            );

            return response()->json(['status' => 'success', 'count' => $count]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|exists:glosa_locales,code',
            'nested' => 'boolean',
        ]);

        $data = $this->exportTranslationsQuery->get(
            $validated['locale'],
            $request->boolean('nested', null)
        );

        $filename = "{$validated['locale']}.json";

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
