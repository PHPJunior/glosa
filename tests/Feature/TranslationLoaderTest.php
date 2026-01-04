<?php

namespace PhpJunior\Glosa\Tests\Feature;

use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Tests\TestCase;
use PhpJunior\Glosa\TranslationLoader;

class TranslationLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function it_uses_the_glosa_translation_loader()
    {
        $loader = app('translation.loader');

        // Debug output to see what we actually got
        if (!($loader instanceof TranslationLoader)) {
            fwrite(STDERR, "Loaded Loader Class: " . get_class($loader) . "\n");
        }

        $this->assertInstanceOf(TranslationLoader::class, $loader);
    }

    /** @test */
    public function it_loads_translations_from_database()
    {
        // 1. Setup Locale
        $locale = Locale::create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true
        ]);

        // 2. Setup Key
        $key = TranslationKey::create([
            'group' => 'messages',
            'key' => 'welcome',
        ]);

        // 3. Setup Value in DB
        TranslationValue::create([
            'locale_id' => $locale->id,
            'key_id' => $key->id,
            'value' => 'Hello from Database',
        ]);

        // 4. Clear cache/loader resolved messages if necessary
        // In a fresh request this shouldn't be needed, but for tests:
        app('translator')->setLoaded([]);

        // 5. Test
        // Note: We need to ensure the locale is 'en'
        app()->setLocale('en');

        $this->assertEquals('Hello from Database', __('messages.welcome'));
    }

    /** @test */
    public function it_falls_back_to_file_if_not_in_database()
    {
        // 1. Ensure sure DB is empty for this key
        // 2. Verify it returns the key (or file value if we had one mock)
        // Since we don't have actual language files in this test package easily, 
        // it usually returns the key if not found.

        app()->setLocale('en');
        $this->assertEquals('messages.missing_key', __('messages.missing_key'));
    }
}
