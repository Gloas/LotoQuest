<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Home;

use Tests\Application\Actions\LotoQuestTestCase;

class RoundTest extends LotoQuestTestCase
{
    public function testRoundPageShowsTitle(): void
    {
        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('Loto adulte, partie 1 quine', $this->_body);
    }


    public function testRoundPageShowsPrizesOfTheRoundOnly(): void
    {
        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('Boulangerie Du Coin', $this->_body);
        $this->assertStringContainsString('Pizzeria Chez Toto', $this->_body);
        $this->assertStringNotContainsString('Fleuriste Rose', $this->_body);
        $this->assertStringNotContainsString('Garage Auto', $this->_body);
    }


    public function testRoundPageShowsRoundsMenu(): void
    {
        $this->_dispatch('/adulte/1/double_quine');
        $this->assertStringContainsString('href="/adulte/1/quine"', $this->_body);
        $this->assertStringContainsString('btn active current_round"href="/adulte/1/double_quine"', $this->_body);
        $this->assertStringContainsString('href="/adulte/1/carton"', $this->_body);
    }


    public function testRoundPageShowsDrawButton(): void
    {
        $this->_dispatch('/enfant/1/double_quine');
        $this->assertStringContainsString('Tirer un nombre', $this->_body);
        $this->assertStringContainsString('href="/enfant/1/double_quine/random"', $this->_body);
    }


    public function testDrawButtonTimeoutDependsOnLoto(): void
    {
        $this->_dispatch('/enfant/1/quine');
        $this->assertStringContainsString('}, 6500);', $this->_body);

        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('}, 4000);', $this->_body);
    }


    public function testBeforeAnyDrawCurrentNumberIsQuestionMark(): void
    {
        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('current_number d-inline-block">?</span>', $this->_body);
        $this->assertStringContainsString('0%</span>', $this->_body);
    }


    public function testNumberTableHasNinetyNumbers(): void
    {
        $this->_dispatch('/adulte/1/quine');
        $this->assertSame(100, substr_count($this->_body, '<td '));
        $this->assertSame(10, substr_count($this->_body, 'not_in_loto'));
    }


    public function testSurpriseHasOnlyCartonAndRevealButton(): void
    {
        $this->_dispatch('/adulte/surprise_rouge/carton');
        $this->assertStringContainsString('Salon De Coiffure', $this->_body);
        $this->assertStringContainsString('Voir les lots ?', $this->_body);
        $this->assertStringNotContainsString('/adulte/surprise_rouge/quine', $this->_body);
    }


    public function testGrosLotShowsItsPrize(): void
    {
        $this->_dispatch('/adulte/gros_lot/carton');
        $this->assertStringContainsString('Parc Aquatique', $this->_body);
        $this->assertStringNotContainsString('/adulte/gros_lot/quine', $this->_body);
    }


    public function testPasDeBolShowsItsPrize(): void
    {
        $this->_dispatch('/adulte/pas_de_bol/carton');
        $this->assertStringContainsString('Restaurant', $this->_body);
    }


    public function testInvalidIdsAreNotEchoedNorUsedAsPath(): void
    {
        $this->_dispatch('/adulte/%3Cscript%3E/quine');
        $this->assertStringNotContainsString('<script>', $this->_body);
        $this->assertDirectoryDoesNotExist($this->drawsDir . '/adulte');
    }
}
