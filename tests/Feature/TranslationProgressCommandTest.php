<?php

namespace PhpJunior\Glosa\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;
use PhpJunior\Glosa\Queries\GetTranslationProgressQuery;
use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationProgressCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $en = Locale::create(['code' => 'en', 'name' => 'en', 'is_default' => true]);
        $kr = Locale::create(['code' => 'kr', 'name' => 'kr']);

        $failed = TranslationKey::create(['group' => 'auth', 'key' => 'failed']);
        $throttle = TranslationKey::create(['group' => 'auth', 'key' => 'throttle']);
        $appUrl = TranslationKey::create(['namespace' => 'client', 'group' => 'labels', 'key' => 'app_url']);

        foreach ([$failed, $throttle, $appUrl] as $key) {
            TranslationValue::create(['key_id' => $key->id, 'locale_id' => $en->id, 'value' => 'EN']);
        }

        TranslationValue::create(['key_id' => $failed->id, 'locale_id' => $kr->id, 'value' => 'KR']);
        // An empty value does not count as translated.
        TranslationValue::create(['key_id' => $throttle->id, 'locale_id' => $kr->id, 'value' => '']);
    }

    #[Test]
    public function it_summarises_progress_per_locale()
    {
        $summary = app(GetTranslationProgressQuery::class)->summary(Locale::orderBy('code')->get());

        $this->assertSame(['total' => 3, 'translated' => 3, 'missing' => 0, 'percent' => 100.0], $summary['en']);
        $this->assertSame(['total' => 3, 'translated' => 1, 'missing' => 2, 'percent' => 33.3], $summary['kr']);
    }

    #[Test]
    public function it_filters_by_group_and_namespace()
    {
        $query = app(GetTranslationProgressQuery::class);
        $locales = Locale::where('code', 'kr')->get();

        $this->assertSame(['total' => 2, 'translated' => 1, 'missing' => 1, 'percent' => 50.0], $query->summary($locales, ['group' => 'auth'])['kr']);
        $this->assertSame(0, $query->summary($locales, ['group' => 'client::labels'])['kr']['translated']);
        $this->assertSame(1, $query->summary($locales, ['namespace' => 'client'])['kr']['total']);
        $this->assertSame(2, $query->summary($locales, ['namespace' => '*'])['kr']['total']);
    }

    #[Test]
    public function it_breaks_progress_down_by_group()
    {
        $byGroup = app(GetTranslationProgressQuery::class)->byGroup(Locale::orderBy('code')->get());

        $this->assertSame(['auth', 'client::labels'], $byGroup->keys()->all());
        $this->assertSame(50.0, $byGroup['auth']['kr']['percent']);
        $this->assertSame(0.0, $byGroup['client::labels']['kr']['percent']);
        $this->assertSame(100.0, $byGroup['client::labels']['en']['percent']);
    }

    #[Test]
    public function it_lists_missing_keys()
    {
        $missing = app(GetTranslationProgressQuery::class)->missingKeys(Locale::where('code', 'kr')->first());

        $this->assertSame(['auth.throttle', 'client::labels.app_url'], $missing->all());
    }

    #[Test]
    public function the_command_prints_progress_and_missing_keys()
    {
        $this->artisan('glosa:progress', ['--by-group' => true, '--missing' => true])
            ->expectsOutputToContain('en (default)')
            ->expectsOutputToContain('33.3%')
            ->expectsOutputToContain('client::labels')
            ->expectsOutputToContain('auth.throttle')
            ->assertSuccessful();
    }

    #[Test]
    public function it_fails_when_a_locale_is_below_the_minimum()
    {
        $this->artisan('glosa:progress', ['--min' => 50])
            ->expectsOutputToContain('kr (33.3%)')
            ->assertFailed();

        $this->artisan('glosa:progress', ['--min' => 30])->assertSuccessful();
        $this->artisan('glosa:progress', ['--min' => 50, '--locale' => ['en']])->assertSuccessful();
    }

    #[Test]
    public function it_fails_for_an_unknown_locale()
    {
        $this->artisan('glosa:progress', ['--locale' => ['xx']])->assertFailed();
    }
}
