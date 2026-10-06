<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Home;

use Tests\Application\Actions\LotoQuestTestCase;

class SpecialPagesTest extends LotoQuestTestCase
{
    public function testEntracteShowsPriceAndTimer(): void
    {
        $this->_dispatch('/adulte/entracte_d_10_20');
        $this->assertStringContainsString('Carton à 10€', $this->_body);
        $this->assertStringContainsString('<span class="timer fs-1 fw-bold ">20</span>', $this->_body);
        $this->assertStringContainsString('var timeLimitInMinutes = 20;', $this->_body);
    }


    public function testEntracteShowsWallOfLogos(): void
    {
        $this->_dispatch('/adulte/entracte_d_10_20');
        $this->assertStringContainsString('masonry', $this->_body);
        $this->assertStringNotContainsString('Tirer un nombre', $this->_body);
    }


    public function testSpectacleShowsTitleDescriptionAndMusic(): void
    {
        $this->_dispatch('/adulte/spectacle');
        $this->assertStringContainsString('Les Danseurs !', $this->_body);
        $this->assertStringContainsString('Bienvenue !<br />Place au spectacle.', $this->_body);
        $this->assertStringContainsString('/assets/logo/danseurs.jpg', $this->_body);
        $this->assertStringContainsString('<audio', $this->_body);
        $this->assertStringContainsString('fireworks.js', $this->_body);
    }


    public function testOutroShowsThanks(): void
    {
        $this->_dispatch('/adulte/outro');
        $this->assertStringContainsString('Merci à tous !', $this->_body);
        $this->assertStringContainsString('/assets/thanks_donators.png', $this->_body);
        $this->assertStringContainsString('/assets/thanks_volunteers.png', $this->_body);
    }


    public function testOutroRouteRendersHome(): void
    {
        $this->_dispatch('/outro');
        $this->assertStringContainsString('Loto adulte', $this->_body);
    }


    public function testResetRendersHome(): void
    {
        $this->_dispatch('/reset');
        $this->assertStringContainsString('Loto adulte', $this->_body);
    }
}
