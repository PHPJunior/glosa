<?php

namespace PhpJunior\Glosa\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_fetch_grouped_translations()
    {
        // Setup data
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'welcome']);
        TranslationValue::create([
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Welcome',
        ]);

        $response = $this->getJson('/glosa/translations/grouped');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'group', 'key_name', 'full_key', 'values'],
                ],
                'locales',
            ]);
    }

    #[Test]
    public function it_can_create_locale()
    {
        $response = $this->postJson(route('glosa.api.locales.store'), [
            'locale' => 'fr',
            'is_default' => true,
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_locales', ['code' => 'fr', 'is_default' => true]);
    }

    #[Test]
    public function it_ensures_only_one_default_locale()
    {
        Locale::create(['code' => 'en', 'is_default' => true]);

        $this->postJson(route('glosa.api.locales.store'), [
            'locale' => 'fr',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('glosa_locales', ['code' => 'en', 'is_default' => false]);
        $this->assertDatabaseHas('glosa_locales', ['code' => 'fr', 'is_default' => true]);
    }

    #[Test]
    public function it_can_update_locale()
    {
        $locale = Locale::create(['code' => 'de', 'name' => 'German']);

        $response = $this->putJson(route('glosa.api.locales.update', $locale->id), [
            'code' => 'de-DE',
            'is_default' => true,
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('glosa_locales', ['id' => $locale->id, 'code' => 'de-DE', 'is_default' => true]);
    }

    #[Test]
    public function it_can_delete_locale()
    {
        $locale = Locale::create(['code' => 'it', 'name' => 'Italian']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'pizza']);
        TranslationValue::create(['key_id' => $key->id, 'locale_id' => $locale->id, 'value' => 'Pizza']);

        $response = $this->deleteJson("/glosa/locales/{$locale->id}");

        $response->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseMissing('glosa_locales', ['id' => $locale->id]);
        $this->assertDatabaseMissing('glosa_values', ['locale_id' => $locale->id]);
    }

    #[Test]
    public function it_can_create_key()
    {
        $response = $this->postJson('/glosa/keys', [
            'group' => 'auth',
            'key' => 'failed',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_keys', [
            'group' => 'auth',
            'key' => 'failed',
        ]);
    }

    #[Test]
    public function it_can_update_translation_value()
    {
        $locale = Locale::create(['code' => 'en']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'hello']);

        $response = $this->putJson('/glosa/translations/value', [
            'key_id' => $key->id,
            'locale' => 'en',
            'value' => 'Hello World',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_values', [
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Hello World',
        ]);
    }

    #[Test]
    public function it_can_rename_key()
    {
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'old_key']);

        $response = $this->putJson("/glosa/keys/{$key->id}", [
            'group' => 'messages',
            'key' => 'new_key',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_keys', [
            'id' => $key->id,
            'group' => 'messages',
            'key' => 'new_key',
        ]);
    }

    #[Test]
    public function it_can_delete_key()
    {
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'to_delete']);
        $locale = Locale::create(['code' => 'en']);
        TranslationValue::create([
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Value',
        ]);

        $response = $this->deleteJson("/glosa/keys/{$key->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('glosa_keys', ['id' => $key->id]);
        $this->assertDatabaseMissing('glosa_values', ['key_id' => $key->id]);
    }

    #[Test]
    public function it_can_import_json_file()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);

        $json = json_encode([
            'messages' => [
                'welcome' => 'Welcome to Glosa',
            ],
            'simple_key' => 'Simple Value',
        ]);

        $file = File::createWithContent('import.json', $json);

        $response = $this->postJson(route('glosa.api.import'), [
            'locale' => 'en',
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success', 'count' => 2]);

        $this->assertDatabaseHas('glosa_keys', ['group' => 'messages', 'key' => 'welcome']);
        $this->assertDatabaseHas('glosa_keys', ['group' => '*', 'key' => 'simple_key']);
        $this->assertDatabaseHas('glosa_values', ['value' => 'Welcome to Glosa']);
        $this->assertDatabaseHas('glosa_values', ['value' => 'Simple Value']);
    }

    #[Test]
    public function it_can_import_without_overwriting_existing_translations()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'welcome']);
        TranslationValue::create([
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Original Welcome',
        ]);

        $json = json_encode([
            'messages' => [
                'welcome' => 'New Welcome',
                'goodbye' => 'Goodbye',
            ],
        ]);

        $file = File::createWithContent('import.json', $json);

        $response = $this->postJson(route('glosa.api.import'), [
            'locale' => 'en',
            'file' => $file,
            'overwrite' => false,
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success', 'count' => 2]);

        $this->assertDatabaseHas('glosa_values', [
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Original Welcome',
        ]);
        $this->assertDatabaseHas('glosa_values', [
            'locale_id' => $locale->id,
            'value' => 'Goodbye',
        ]);
    }

    #[Test]
    public function it_creates_namespaced_keys_from_a_prefixed_group()
    {
        $locale = Locale::create(['code' => 'kr', 'name' => 'kr']);

        $response = $this->postJson('/glosa/keys', ['group' => 'client::labels', 'key' => 'app_url']);

        $response->assertStatus(200)
            ->assertJsonPath('key.namespace', 'client')
            ->assertJsonPath('key.group', 'labels')
            ->assertJsonPath('key.qualified_group', 'client::labels')
            ->assertJsonPath('key.full_key', 'client::labels.app_url');

        $this->putJson('/glosa/translations/value', [
            'key_id' => $response->json('key.id'),
            'locale' => 'kr',
            'value' => 'KR from DB',
        ])->assertStatus(200);

        app('translator')->setLoaded([]);
        app()->setLocale('kr');

        $this->assertSame('KR from DB', __('client::labels.app_url'));
    }

    #[Test]
    public function updating_a_namespaced_key_keeps_its_namespace()
    {
        $key = TranslationKey::create(['namespace' => 'client', 'group' => 'labels', 'key' => 'app_url']);

        $this->putJson("/glosa/keys/{$key->id}", ['group' => 'client::labels', 'key' => 'api_key'])
            ->assertStatus(200);

        $this->assertSame(['client', 'labels', 'api_key'], [
            $key->fresh()->namespace, $key->fresh()->group, $key->fresh()->key,
        ]);
    }

    #[Test]
    public function groups_and_group_filter_are_namespace_aware()
    {
        TranslationKey::create(['group' => 'labels', 'key' => 'global']);
        TranslationKey::create(['namespace' => 'client', 'group' => 'labels', 'key' => 'app_url']);

        $this->getJson('/glosa/groups')->assertExactJson(['labels', 'client::labels']);

        $this->getJson('/glosa/translations/grouped?group='.urlencode('client::labels'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_key', 'client::labels.app_url');
    }
}
