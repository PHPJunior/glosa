<?php

namespace PhpJunior\Glosa\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImportLangCommandTest extends TestCase
{
    use RefreshDatabase;

    protected string $langPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = sys_get_temp_dir().'/glosa-lang-'.uniqid();
        $files = new Filesystem;

        $files->ensureDirectoryExists($this->langPath.'/en/admin');
        $files->ensureDirectoryExists($this->langPath.'/fr');
        $files->ensureDirectoryExists($this->langPath.'/vendor/package/en');

        $files->put($this->langPath.'/en/auth.php', "<?php return ['failed' => 'Bad credentials', 'custom' => ['email' => ['required' => 'Email please']], 'empty' => []];");
        $files->put($this->langPath.'/en/admin/users.php', "<?php return ['title' => 'Users'];");
        $files->put($this->langPath.'/fr/auth.php', "<?php return ['failed' => 'Identifiants invalides'];");
        $files->put($this->langPath.'/vendor/package/en/messages.php', "<?php return ['hello' => 'Hi'];");
        $files->put($this->langPath.'/en.json', json_encode(['Hello. World' => 'Hello. World', 'Log in' => 'Log in']));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->langPath);

        parent::tearDown();
    }

    protected function value(string $locale, string $group, string $key, string $namespace = '*'): ?string
    {
        return TranslationValue::query()
            ->whereHas('locale', fn ($q) => $q->where('code', $locale))
            ->whereHas('translationKey', fn ($q) => $q->where('namespace', $namespace)->where('group', $group)->where('key', $key))
            ->value('value');
    }

    protected function makeClientModule(): string
    {
        $path = $this->langPath.'/Modules/Client/lang';

        $files = new Filesystem;
        $files->ensureDirectoryExists($path.'/en');
        $files->put($path.'/en/labels.php', "<?php return ['app_url' => 'App URL', 'api_key' => 'API Key'];");

        return $path;
    }

    #[Test]
    public function it_imports_php_and_json_files_from_a_lang_directory()
    {
        $this->artisan('glosa:import', ['path' => $this->langPath])->assertSuccessful();

        $this->assertEqualsCanonicalizing(['en', 'fr'], Locale::pluck('code')->all());
        $this->assertTrue(Locale::where('code', 'en')->value('is_default'));

        $this->assertSame('Bad credentials', $this->value('en', 'auth', 'failed'));
        $this->assertSame('Email please', $this->value('en', 'auth', 'custom.email.required'));
        $this->assertSame('Users', $this->value('en', 'admin/users', 'title'));
        $this->assertSame('Identifiants invalides', $this->value('fr', 'auth', 'failed'));
        $this->assertSame('Hello. World', $this->value('en', '*', 'Hello. World'));

        $this->assertSame('Hi', $this->value('en', 'messages', 'hello', 'package'));
        $this->assertFalse(TranslationKey::where('namespace', '*')->where('group', 'messages')->exists());
        $this->assertFalse(TranslationKey::where('key', 'empty')->exists());
    }

    #[Test]
    public function imported_nested_keys_resolve_through_the_translator()
    {
        $this->artisan('glosa:import', ['path' => $this->langPath])->assertSuccessful();

        app('translator')->setLoaded([]);
        app()->setLocale('en');

        $this->assertSame('Email please', __('auth.custom.email.required'));
        $this->assertSame('Users', __('admin/users.title'));
        $this->assertSame('Hello. World', __('Hello. World'));
    }

    #[Test]
    public function it_keeps_existing_values_unless_overwrite_is_given()
    {
        $this->artisan('glosa:import', ['path' => $this->langPath])->assertSuccessful();

        TranslationValue::query()->update(['value' => 'Edited']);

        $this->artisan('glosa:import', ['path' => $this->langPath])->assertSuccessful();
        $this->assertSame('Edited', $this->value('en', 'auth', 'failed'));

        $this->artisan('glosa:import', ['path' => $this->langPath, '--overwrite' => true])->assertSuccessful();
        $this->assertSame('Bad credentials', $this->value('en', 'auth', 'failed'));
    }

    #[Test]
    public function it_can_limit_the_import_to_given_locales()
    {
        $this->artisan('glosa:import', ['path' => $this->langPath, '--locale' => ['fr']])->assertSuccessful();

        $this->assertSame(['fr'], Locale::pluck('code')->all());
        $this->assertSame(1, TranslationValue::count());
    }

    #[Test]
    public function it_imports_every_directory_matching_a_glob_pattern()
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists($this->langPath.'/Modules/Billing/lang/en');
        $files->ensureDirectoryExists($this->langPath.'/Modules/Shipping/lang/en');
        $files->put($this->langPath.'/Modules/Billing/lang/en/billing.php', "<?php return ['invoice' => 'Invoice'];");
        $files->put($this->langPath.'/Modules/Shipping/lang/en/shipping.php', "<?php return ['parcel' => 'Parcel'];");

        $this->artisan('glosa:import', ['path' => [$this->langPath.'/Modules/*/lang']])->assertSuccessful();

        $this->assertSame('Invoice', $this->value('en', 'billing', 'invoice'));
        $this->assertSame('Parcel', $this->value('en', 'shipping', 'parcel'));
        $this->assertSame(2, TranslationValue::count());
    }

    #[Test]
    public function it_accepts_multiple_paths()
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists($this->langPath.'/Modules/Billing/lang/en');
        $files->put($this->langPath.'/Modules/Billing/lang/en/billing.php', "<?php return ['invoice' => 'Invoice'];");

        $this->artisan('glosa:import', ['path' => [$this->langPath, $this->langPath.'/Modules/Billing/lang']])->assertSuccessful();

        $this->assertSame('Bad credentials', $this->value('en', 'auth', 'failed'));
        $this->assertSame('Invoice', $this->value('en', 'billing', 'invoice'));
    }

    #[Test]
    public function it_detects_the_namespace_of_registered_module_paths()
    {
        $path = $this->makeClientModule();
        app('translator')->addNamespace('client', $path);

        $this->artisan('glosa:import', ['path' => [$this->langPath.'/Modules/*/lang']])->assertSuccessful();

        $this->assertSame('App URL', $this->value('en', 'labels', 'app_url', 'client'));
        $this->assertFalse(TranslationKey::where('namespace', '*')->where('group', 'labels')->exists());

        // The translator should now read the namespaced value from the database.
        TranslationValue::query()->update(['value' => 'From DB']);
        app('translator')->setLoaded([]);
        app()->setLocale('en');

        $this->assertSame('From DB', __('client::labels.app_url'));
    }

    #[Test]
    public function the_namespace_can_be_given_explicitly()
    {
        $path = $this->makeClientModule();

        $this->artisan('glosa:import', ['path' => [$path], '--namespace' => 'client'])->assertSuccessful();

        $this->assertSame('API Key', $this->value('en', 'labels', 'api_key', 'client'));
    }

    #[Test]
    public function it_fails_when_a_glob_matches_nothing()
    {
        $this->artisan('glosa:import', ['path' => [$this->langPath.'/Nope/*/lang']])->assertFailed();
    }

    #[Test]
    public function it_fails_for_a_missing_directory()
    {
        $this->artisan('glosa:import', ['path' => $this->langPath.'/missing'])->assertFailed();
    }
}
