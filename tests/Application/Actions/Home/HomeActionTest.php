<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Home;

use Tests\Application\Actions\LotoQuestTestCase;

class HomeActionTest extends LotoQuestTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->_dispatch('/');
    }


    public function testBootstrapMinCssIsLoaded(): void
    {
        $this->assertStringContainsString('bootstrap.min.css', $this->_body);
    }


    public function testBootstrapBundleMinJsIsLoaded(): void
    {
        $this->assertStringContainsString('bootstrap.bundle.min.js', $this->_body);
    }


    public function testNavIsInBody(): void
    {
        $this->assertStringContainsString('navbar navbar-expand-lg', $this->_body);
    }


    public function testMenuListsEveryLotoOfTheSheet(): void
    {
        $this->assertStringContainsString('Loto adulte', $this->_body);
        $this->assertStringContainsString('Loto enfant', $this->_body);
    }


    public function testMenuLinksToParties(): void
    {
        $this->assertStringContainsString('href="/adulte/1/quine"', $this->_body);
        $this->assertStringContainsString('href="/adulte/surprise_rouge/carton"', $this->_body);
        $this->assertStringContainsString('href="/adulte/entracte_d_10_20"', $this->_body);
        $this->assertStringContainsString('href="/enfant/gros_lot/carton"', $this->_body);
    }


    public function testHomeShowsWallOfDonatorsLogos(): void
    {
        $this->assertStringContainsString('masonry', $this->_body);
        $this->assertStringContainsString('/assets/logo/garage.png', $this->_body);
        $this->assertStringContainsString('/assets/logo/console.png', $this->_body);
    }


    public function testHomeShowsResetButton(): void
    {
        $this->assertStringContainsString('Réinitialiser les tirages', $this->_body);
    }


    public function testNumberTableShouldNotBePresent(): void
    {
        $this->assertStringNotContainsString('<table', $this->_body);
    }


    public function testCurrentNumberShouldNotBePresent(): void
    {
        $this->assertStringNotContainsString('current_number', $this->_body);
    }
}
