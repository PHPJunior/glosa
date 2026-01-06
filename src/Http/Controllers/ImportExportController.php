<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpJunior\Glosa\Actions\ImportTranslationsAction;
use PhpJunior\Glosa\Queries\ExportTranslationsQuery;

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

    public function import(Request $request)
    {
        $request->validate([
            'locale' => 'required|exists:glosa_locales,code',
            'file' => 'required|file|mimetypes:application/json,text/plain',
        ]);

        try {
            $count = $this->importTranslationsAction->execute(
                $request->locale,
                $request->file('file')->getRealPath()
            );
            return response()->json(['status' => 'success', 'count' => $count]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'locale' => 'required|exists:glosa_locales,code',
            'nested' => 'boolean'
        ]);

        $data = $this->exportTranslationsQuery->get(
            $request->locale,
            $request->boolean('nested', null) // Pass null if not present to let query invoke config default
        );

        $filename = "{$request->locale}.json";

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
