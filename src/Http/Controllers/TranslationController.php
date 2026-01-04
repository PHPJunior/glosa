<?php

namespace PhpJunior\Glosa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Http\Resources\TranslationKeyResource;
use PhpJunior\Glosa\Http\Resources\LocaleResource;

class TranslationController extends Controller
{
    public function index()
    {
        return view('glosa::index');
    }

    public function groups()
    {
        return TranslationKey::select('group')->distinct()->pluck('group');
    }

    public function grouped(Request $request)
    {
        $limit = $request->input('limit', 20);

        $keys = TranslationKey::with(['values.locale'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('key', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('group'), function ($q) use ($request) {
                $q->where('group', $request->group);
            })
            ->when($request->filled('missing_locale'), function ($q) use ($request) {
                $localeCode = $request->missing_locale;
                $q->whereDoesntHave('values', function ($values) use ($localeCode) {
                    $values->whereHas('locale', function ($locale) use ($localeCode) {
                        $locale->where('code', $localeCode);
                    })->whereNotNull('value')->where('value', '!=', '');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        $locales = Locale::all();

        return TranslationKeyResource::collection($keys)->additional([
            'locales' => LocaleResource::collection($locales)
        ]);
    }

    public function updateValue(Request $request)
    {
        $data = $request->validate([
            'key_id' => 'required|exists:glosa_keys,id',
            'locale' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $locale = Locale::where('code', $data['locale'])->firstOrFail();

        TranslationValue::updateOrCreate(
            [
                'key_id' => $data['key_id'],
                'locale_id' => $locale->id,
            ],
            [
                'value' => $data['value']
            ]
        );

        return response()->json(['status' => 'success']);
    }

    public function storeLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|max:10|unique:glosa_locales,code',
            'is_default' => 'boolean'
        ]);

        if ($request->boolean('is_default')) {
            Locale::query()->update(['is_default' => false]);
        }

        $locale = Locale::create([
            'code' => $validated['locale'],
            'name' => $validated['locale'],
            'is_default' => $request->boolean('is_default')
        ]);

        return response()->json(['status' => 'success', 'locale' => new LocaleResource($locale)]);
    }

    public function updateLocale(Request $request, $id)
    {
        $locale = Locale::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:glosa_locales,code,' . $id,
            'is_default' => 'boolean'
        ]);

        if ($request->boolean('is_default')) {
            Locale::where('id', '!=', $id)->update(['is_default' => false]);
        }

        $locale->update([
            'code' => $validated['code'],
            'name' => $validated['code'],
            'is_default' => $request->boolean('is_default')
        ]);

        return response()->json(['status' => 'success', 'locale' => new LocaleResource($locale)]);
    }

    public function destroyLocale($id)
    {
        $locale = Locale::findOrFail($id);

        TranslationValue::where('locale_id', $locale->id)->delete();

        $locale->delete();

        return response()->json(['status' => 'success']);
    }

    public function storeKey(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|string',
            'key' => 'required|string',
        ]);

        $key = TranslationKey::firstOrCreate([
            'group' => $validated['group'],
            'key' => $validated['key'],
        ]);

        return response()->json(['status' => 'success', 'key' => new TranslationKeyResource($key)]);
    }

    public function updateKey(Request $request, $id)
    {
        $key = TranslationKey::findOrFail($id);

        $validated = $request->validate([
            'group' => 'required|string',
            'key' => 'required|string',
        ]);

        $key->update([
            'group' => $validated['group'],
            'key' => $validated['key'],
        ]);

        return response()->json(['status' => 'success', 'key' => new TranslationKeyResource($key)]);
    }

    public function destroyKey($id)
    {
        $key = TranslationKey::findOrFail($id);

        TranslationValue::where('key_id', $key->id)->delete();
        $key->delete();

        return response()->json(['status' => 'success']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'locale' => 'required|exists:glosa_locales,code',
            'file' => 'required|file|mimetypes:application/json,text/plain',
        ]);

        $locale = Locale::where('code', $request->locale)->firstOrFail();
        $file = $request->file('file');
        $json = json_decode(file_get_contents($file->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid JSON file'], 422);
        }

        $flattened = Arr::dot($json);

        foreach ($flattened as $key => $value) {
            if (is_array($value)) {
                continue; // Should strictly be strings at the leaf, but just in case
            }
            // Determine group and key
            // Strategy: everything before the last dot is group, after is key.
            // If no dot, group is '*' and key is the string.
            if (strpos($key, '.') !== false) {
                $lastDot = strrpos($key, '.');
                $group = substr($key, 0, $lastDot);
                $keyName = substr($key, $lastDot + 1);
            } else {
                $group = '*';
                $keyName = $key;
            }

            $translationKey = TranslationKey::firstOrCreate([
                'group' => $group,
                'key' => $keyName,
            ]);

            TranslationValue::updateOrCreate(
                [
                    'key_id' => $translationKey->id,
                    'locale_id' => $locale->id,
                ],
                [
                    'value' => (string) $value
                ]
            );
        }

        return response()->json(['status' => 'success', 'count' => count($flattened)]);
    }

    public function publicTranslations($locale)
    {
        $localeModel = Locale::where('code', $locale)->firstOrFail();

        $translations = TranslationValue::where('locale_id', $localeModel->id)
            ->with('translationKey')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->translationKey;
                $fullKey = $key->group === '*' ? $key->key : "{$key->group}.{$key->key}";
                return [$fullKey => $item->value];
            });

        if (config('glosa.public_api_nested', true)) {
            return response()->json(Arr::undot($translations->toArray()));
        }

        return response()->json($translations);
    }
    public function export(Request $request)
    {
        $request->validate([
            'locale' => 'required|exists:glosa_locales,code',
            'nested' => 'boolean'
        ]);

        $locale = Locale::where('code', $request->locale)->firstOrFail();

        $translations = TranslationValue::where('locale_id', $locale->id)
            ->with('translationKey')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->translationKey;
                $fullKey = $key->group === '*' ? $key->key : "{$key->group}.{$key->key}";
                return [$fullKey => $item->value];
            });

        $data = $translations->toArray();

        // Check if nested is requested (default to config if not provided)
        $shouldNest = $request->has('nested')
            ? $request->boolean('nested')
            : config('glosa.public_api_nested', true);

        if ($shouldNest) {
            $data = Arr::undot($data);
        }

        $filename = "{$locale->code}.json";

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
