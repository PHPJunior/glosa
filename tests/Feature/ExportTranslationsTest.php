<?php

namespace PhpJunior\Glosa\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Queries\ExportTranslationsQuery;
use PhpJunior\Glosa\Queries\GetPublicTranslationsQuery;
use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExportTranslationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_falls_back_to_default_locale_for_missing_or_empty_values()
    {
        $en = Locale::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $fr = Locale::create(['code' => 'fr', 'name' => 'French']);

        $hello = TranslationKey::create(['group' => 'messages', 'key' => 'hello']);
        $bye = TranslationKey::create(['group' => 'messages', 'key' => 'bye']);
        $thanks = TranslationKey::create(['group' => 'messages', 'key' => 'thanks']);
        $zero = TranslationKey::create(['group' => 'messages', 'key' => 'zero']);

        TranslationValue::create(['key_id' => $hello->id, 'locale_id' => $en->id, 'value' => 'Hello']);
        TranslationValue::create(['key_id' => $bye->id, 'locale_id' => $en->id, 'value' => 'Bye']);
        TranslationValue::create(['key_id' => $bye->id, 'locale_id' => $fr->id, 'value' => '']);
        TranslationValue::create(['key_id' => $thanks->id, 'locale_id' => $en->id, 'value' => 'Thanks']);
        TranslationValue::create(['key_id' => $thanks->id, 'locale_id' => $fr->id, 'value' => 'Merci']);
        TranslationValue::create(['key_id' => $zero->id, 'locale_id' => $en->id, 'value' => 'Zero']);
        TranslationValue::create(['key_id' => $zero->id, 'locale_id' => $fr->id, 'value' => '0']);

        $data = app(ExportTranslationsQuery::class)->get('fr', false);

        $this->assertSame([
            'messages.hello' => 'Hello',   // no fr row -> en
            'messages.bye' => 'Bye',       // empty fr -> en
            'messages.thanks' => 'Merci',  // translated -> fr
            'messages.zero' => '0',        // "0" is a real translation
        ], $data);
    }

    #[Test]
    public function it_exports_via_endpoint_with_fallback()
    {
        $en = Locale::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Locale::create(['code' => 'fr', 'name' => 'French']);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'hello']);
        TranslationValue::create(['key_id' => $key->id, 'locale_id' => $en->id, 'value' => 'Hello']);

        $response = $this->get('/glosa/export?locale=fr&nested=1');

        $response->assertOk();
        $this->assertSame(['messages' => ['hello' => 'Hello']], json_decode($response->streamedContent(), true));
    }

    #[Test]
    public function it_uses_config_nesting_when_nested_param_is_omitted()
    {
        $en = Locale::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $key = TranslationKey::create(['group' => 'messages', 'key' => 'hello']);
        TranslationValue::create(['key_id' => $key->id, 'locale_id' => $en->id, 'value' => 'Hello']);

        config(['glosa.public_api_nested' => true]);
        $this->assertSame(['messages' => ['hello' => 'Hello']], json_decode($this->get('/glosa/export?locale=en')->streamedContent(), true));

        config(['glosa.public_api_nested' => false]);
        $this->assertSame(['messages.hello' => 'Hello'], json_decode($this->get('/glosa/export?locale=en')->streamedContent(), true));

        // Explicit param still wins over config
        $this->assertSame(['messages' => ['hello' => 'Hello']], json_decode($this->get('/glosa/export?locale=en&nested=1')->streamedContent(), true));
    }

    #[Test]
    public function public_api_and_loader_keep_zero_and_fall_back_on_empty()
    {
        $en = Locale::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $fr = Locale::create(['code' => 'fr', 'name' => 'French']);

        $zero = TranslationKey::create(['group' => 'messages', 'key' => 'zero']);
        $bye = TranslationKey::create(['group' => 'messages', 'key' => 'bye']);
        TranslationValue::create(['key_id' => $zero->id, 'locale_id' => $en->id, 'value' => 'Zero']);
        TranslationValue::create(['key_id' => $zero->id, 'locale_id' => $fr->id, 'value' => '0']);
        TranslationValue::create(['key_id' => $bye->id, 'locale_id' => $en->id, 'value' => 'Bye']);
        TranslationValue::create(['key_id' => $bye->id, 'locale_id' => $fr->id, 'value' => '']);

        config(['glosa.public_api_nested' => false]);
        $public = app(GetPublicTranslationsQuery::class)->get('fr')->toArray();
        $this->assertSame('0', $public['messages.zero']);
        $this->assertSame('Bye', $public['messages.bye']);

        app('translator')->setLoaded([]);
        app()->setLocale('fr');
        $this->assertSame('0', __('messages.zero'));
        $this->assertSame('Bye', __('messages.bye'));
    }
}
