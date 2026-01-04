<?php

namespace PhpJunior\Glosa\Tests\Feature;

use PhpJunior\Glosa\Tests\TestCase;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_grouped_translations()
    {
        // Setup data
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'welcome']);
        TranslationValue::create([
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Welcome'
        ]);

        $response = $this->getJson("/glosa/translations/grouped");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'group', 'key_name', 'full_key', 'values']
                ],
                'locales'
            ]);
    }

    /** @test */
    public function it_can_create_locale()
    {
        $response = $this->postJson(route('glosa.api.locales.store'), [
            'locale' => 'fr',
            'is_default' => true
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_locales', ['code' => 'fr', 'is_default' => true]);
    }

    /** @test */
    public function it_ensures_only_one_default_locale()
    {
        Locale::create(['code' => 'en', 'is_default' => true]);

        $this->postJson(route('glosa.api.locales.store'), [
            'locale' => 'fr',
            'is_default' => true
        ]);

        $this->assertDatabaseHas('glosa_locales', ['code' => 'en', 'is_default' => false]);
        $this->assertDatabaseHas('glosa_locales', ['code' => 'fr', 'is_default' => true]);
    }

    /** @test */
    public function it_can_update_locale()
    {
        $locale = Locale::create(['code' => 'de', 'name' => 'German']);

        $response = $this->putJson(route('glosa.api.locales.update', $locale->id), [
            'code' => 'de-DE',
            'is_default' => true
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('glosa_locales', ['id' => $locale->id, 'code' => 'de-DE', 'is_default' => true]);
    }

    /** @test */
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

    /** @test */
    public function it_can_create_key()
    {
        $response = $this->postJson("/glosa/keys", [
            'group' => 'auth',
            'key' => 'failed'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_keys', [
            'group' => 'auth',
            'key' => 'failed'
        ]);
    }

    /** @test */
    public function it_can_update_translation_value()
    {
        $locale = Locale::create(['code' => 'en']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'hello']);

        $response = $this->putJson("/glosa/translations/value", [
            'key_id' => $key->id,
            'locale' => 'en',
            'value' => 'Hello World'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_values', [
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Hello World'
        ]);
    }

    /** @test */
    public function it_can_rename_key()
    {
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'old_key']);

        $response = $this->putJson("/glosa/keys/{$key->id}", [
            'group' => 'messages',
            'key' => 'new_key'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('glosa_keys', [
            'id' => $key->id,
            'group' => 'messages',
            'key' => 'new_key'
        ]);
    }

    /** @test */
    public function it_can_delete_key()
    {
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'to_delete']);
        $locale = Locale::create(['code' => 'en']);
        TranslationValue::create([
            'key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Value'
        ]);

        $response = $this->deleteJson("/glosa/keys/{$key->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('glosa_keys', ['id' => $key->id]);
        $this->assertDatabaseMissing('glosa_values', ['key_id' => $key->id]);
    }
    /** @test */
    public function it_can_import_json_file()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);

        $json = json_encode([
            'messages' => [
                'welcome' => 'Welcome to Glosa'
            ],
            'simple_key' => 'Simple Value'
        ]);

        $file = \Illuminate\Http\Testing\File::createWithContent('import.json', $json);

        $response = $this->postJson(route('glosa.api.import'), [
            'locale' => 'en',
            'file' => $file
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success', 'count' => 2]);

        $this->assertDatabaseHas('glosa_keys', ['group' => 'messages', 'key' => 'welcome']);
        $this->assertDatabaseHas('glosa_keys', ['group' => '*', 'key' => 'simple_key']);
        $this->assertDatabaseHas('glosa_values', ['value' => 'Welcome to Glosa']);
        $this->assertDatabaseHas('glosa_values', ['value' => 'Simple Value']);
    }
}
