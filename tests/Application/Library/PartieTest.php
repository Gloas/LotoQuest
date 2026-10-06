<?php

declare(strict_types=1);

namespace Tests\Application\Library;

use App\Application\Library\Partie;
use PHPUnit\Framework\TestCase;

class PartieTest extends TestCase
{
    public function testNumberedPartie(): void
    {
        $partie = new Partie(['Partie Adulte n°3', '', '', '', '', '', '']);
        $this->assertSame('3', $partie->getId());
        $this->assertSame('/quine', $partie->getFirstRound());
        $this->assertSame('partie n°3', $partie->getAnchorLabel());
    }


    public function testSurprisePartie(): void
    {
        $partie = new Partie(['Surprise rouge', '', '', '', '', '', '']);
        $this->assertSame('surprise_rouge', $partie->getId());
        $this->assertSame('/carton', $partie->getFirstRound());
        $this->assertSame('surprise rouge', $partie->getAnchorLabel());
    }


    public function testGrosLotAndPasDeBol(): void
    {
        $this->assertSame('gros_lot', (new Partie(['Gros lot', '', '', '', '', '', '']))->getId());
        $this->assertSame('pas_de_bol', (new Partie(['Pas de bol', '', '', '', '', '', '']))->getId());
    }


    public function testEntracte(): void
    {
        $partie = new Partie(['Entracte', '1', '10,00€', '20', '', '', '']);
        $this->assertSame('entracte_d_10_20', $partie->getId());
        $this->assertSame('', $partie->getFirstRound());
        $this->assertSame(10, $partie->getPrix());
        $this->assertSame(20, $partie->getMinutes());
        $this->assertSame('entracte 20 minutes', $partie->getAnchorLabel());
    }


    public function testOutro(): void
    {
        $partie = new Partie(['Outro', 'Merci !', 'donators.png', 'volunteers.png', '', '', '']);
        $this->assertTrue($partie->isOutro());
        $this->assertFalse($partie->isSpectacle());
        $this->assertSame('', $partie->getFirstRound());
        $this->assertSame('Merci !', $partie->getThanksMessage());
        $this->assertSame('donators.png', $partie->getThanksDonatorsImg());
        $this->assertSame('volunteers.png', $partie->getThanksVolunteersImg());
    }


    public function testSpectacle(): void
    {
        $partie = new Partie(['Spectacle', 'Le show', 'Ligne 1\nLigne 2', '', '', '', 'show.jpg']);
        $this->assertTrue($partie->isSpectacle());
        $this->assertSame('', $partie->getFirstRound());
        $this->assertSame('Le show', $partie->getShowTitle());
        $this->assertSame('Ligne 1<br />Ligne 2', $partie->getShowDesc());
        $this->assertSame('show.jpg', $partie->getShowImg());
    }


    public function testIntro(): void
    {
        $this->assertTrue((new Partie(['Intro', '', '', '', '', '', '']))->isIntro());
    }
}
