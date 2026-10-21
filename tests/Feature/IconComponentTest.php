<?php

namespace Tests\Feature;

use DOMDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IconComponentTest extends TestCase
{
    public function test_icons_used_in_views_exist_in_the_sprite(): void
    {
        $sprite = new DOMDocument;
        $this->assertTrue($sprite->load(public_path('icons/tabler/sprite.svg')));
        $symbols = [];

        foreach ($sprite->getElementsByTagName('symbol') as $symbol) {
            $symbols[] = $symbol->getAttribute('id');
        }

        $this->assertNotEmpty($symbols);

        foreach (File::allFiles(resource_path('views')) as $view) {
            preg_match_all('/<x-(?:icon|stat-card)\b[^>]*\s(?:name|icon)="([^"]+)"/', $view->getContents(), $matches);

            foreach ($matches[1] as $name) {
                $this->assertContains($name, $symbols, $view->getRelativePathname().': '.$name);
            }
        }
    }

    public function test_default_icon_uses_the_sprite_and_decorative_attributes(): void
    {
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML(trim(Blade::render('<x-icon name="package" />'))));
        $svg = $document->documentElement;

        foreach (['width' => '24', 'height' => '24', 'stroke-width' => '1.8', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'aria-hidden' => 'true', 'focusable' => 'false'] as $attribute => $expected) {
            $this->assertSame($expected, $svg->getAttribute($attribute));
        }

        $this->assertSame(asset('icons/tabler/sprite.svg').'#package', $svg->getElementsByTagName('use')->item(0)->getAttribute('href'));
    }

    public function test_size_sets_both_dimensions(): void
    {
        foreach (['20', '1em'] as $size) {
            $document = new DOMDocument;
            $this->assertTrue($document->loadXML(trim(Blade::render('<x-icon name="package" :size="$size" />', compact('size')))));

            $this->assertSame($size, $document->documentElement->getAttribute('width'));
            $this->assertSame($size, $document->documentElement->getAttribute('height'));
            $this->assertFalse($document->documentElement->hasAttribute('size'));
        }
    }

    public function test_custom_attributes_override_defaults_and_preserve_classes(): void
    {
        $html = Blade::render('<x-icon name="search" size="32" width="1em" height="18" class="nav-icon" stroke-width="2" data-testid="custom-icon" role="img" aria-hidden="false" aria-label="Buscar" focusable="true" />');
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML(trim($html)));
        $svg = $document->documentElement;

        foreach (['width' => '1em', 'height' => '18', 'stroke-width' => '2', 'data-testid' => 'custom-icon', 'role' => 'img', 'aria-hidden' => 'false', 'aria-label' => 'Buscar', 'focusable' => 'true'] as $attribute => $expected) {
            $this->assertSame($expected, $svg->getAttribute($attribute));
        }

        $classes = explode(' ', $svg->getAttribute('class'));
        $this->assertContains('icon', $classes);
        $this->assertContains('nav-icon', $classes);
    }

    public function test_stat_cards_render_direct_symbols_and_default_to_dashboard(): void
    {
        foreach (['clipboard-list', 'coin', 'package', 'users'] as $icon) {
            $html = Blade::render('<x-stat-card label="Indicador" value="10" :icon="$icon" />', compact('icon'));

            $this->assertStringContainsString('#'.$icon.'"', $html);
            $this->assertSame(1, substr_count($html, '<svg'));
            $this->assertStringContainsString('bg-brand-50 text-brand-700', $html);
        }

        $this->assertStringContainsString('#layout-dashboard"', Blade::render('<x-stat-card label="Indicador" value="10" />'));
    }
}
