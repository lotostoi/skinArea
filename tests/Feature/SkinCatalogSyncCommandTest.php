<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SkinCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SkinCatalogSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_inserts_rows_from_json(): void
    {
        Config::set('skinsarena.skin_catalog.source_url', 'https://test.example/skins.json');

        Http::fake([
            'https://test.example/skins.json' => Http::response([
                [
                    'id' => 'skin-test-1',
                    'name' => 'AK-47 | Test',
                    'image' => 'https://cdn.example/i.png',
                    'rarity' => ['id' => 'rarity_rare_weapon'],
                    'category' => ['id' => 'sfui_invpanel_filter_rifle'],
                    'weapon' => ['name' => 'AK-47'],
                ],
                [
                    'name' => 'No external id',
                ],
            ], 200),
        ]);

        $this->artisan('skins:catalog-sync', ['--limit' => '5'])
            ->assertExitCode(0);

        $this->assertSame(1, SkinCatalogItem::query()->count());
        $row = SkinCatalogItem::query()->firstOrFail();
        $this->assertSame('skin-test-1', $row->external_id);
        $this->assertSame('AK-47 | Test', $row->name);
        $this->assertSame('https://cdn.example/i.png', $row->image_url);
        $this->assertSame('mil_spec', $row->rarity);
        $this->assertSame('rifles', $row->category);
    }
}
