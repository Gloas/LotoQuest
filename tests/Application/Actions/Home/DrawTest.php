<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Home;

use Tests\Application\Actions\LotoQuestTestCase;

class DrawTest extends LotoQuestTestCase
{
    public function testDrawRedirectsToRound(): void
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->assertSame(302, $this->_response->getStatusCode());
        $this->assertSame('/adulte/1/quine', $this->_response->getHeaderLine('Location'));
    }


    public function testDrawStoresNumberInSessionFile(): void
    {
        $this->_dispatch('/adulte/1/quine/random');

        $numbers = $this->_drawnNumbers('adulte', '1');
        $this->assertCount(1, $numbers);
        $this->assertGreaterThanOrEqual(1, $numbers[0]);
        $this->assertLessThanOrEqual(90, $numbers[0]);
    }


    public function testDrawnNumberIsShownAndHighlighted(): void
    {
        $this->_dispatch('/adulte/1/quine/random');
        $number = $this->_drawnNumbers('adulte', '1')[0];

        $this->assertStringContainsString('current_number d-inline-block">' . $number . '</span>', $this->_body);
        $this->assertStringContainsString('current_number_in_table fw-bold">' . $number . '</td>', $this->_body);
    }


    public function testLastDrawnNumberIsShownAfterRedirect(): void
    {
        $this->_dispatch('/adulte/1/quine/random');
        $number = $this->_drawnNumbers('adulte', '1')[0];

        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('current_number d-inline-block">' . $number . '</span>', $this->_body);
    }


    public function testPreviousNumberIsHighlighted(): void
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->_dispatch('/adulte/1/quine/random');
        [$previous, $last] = $this->_drawnNumbers('adulte', '1');

        $this->_dispatch('/adulte/1/quine');
        $this->assertStringContainsString('picked previous_number_in_table">' . $previous . '</td>', $this->_body);
        $this->assertStringContainsString('current_number_in_table fw-bold">' . $last . '</td>', $this->_body);
    }


    public function testDrawsAreSharedBetweenRoundsOfAPartie(): void
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->_dispatch('/adulte/1/carton/random');

        $this->assertCount(2, $this->_drawnNumbers('adulte', '1'));
    }


    public function testNinetyDrawsGiveEveryNumberOnce(): void
    {
        for ($i = 0; $i < 95; $i++) {
            $this->_dispatch('/enfant/1/carton/random');
        }

        $numbers = $this->_drawnNumbers('enfant', '1');
        sort($numbers);
        $this->assertSame(range(1, 90), $numbers);
        $this->assertStringContainsString('100%</span>', $this->_body);
    }


    public function testSessionsDoNotShareDraws(): void
    {
        $this->_dispatch('/adulte/1/quine/random', 'alice');
        $this->_dispatch('/adulte/1/quine', 'bob');

        $this->assertCount(1, $this->_drawnNumbers('adulte', '1', 'alice'));
        $this->assertSame([], $this->_drawnNumbers('adulte', '1', 'bob'));
        $this->assertStringContainsString('current_number d-inline-block">?</span>', $this->_body);
    }


    public function testMaliciousSessionCookieCannotEscapeDrawsDirectory(): void
    {
        $this->_dispatch('/adulte/1/quine/random', '../../evil');

        $this->assertCount(1, $this->_drawnNumbers('adulte', '1', 'evil'));
        $this->assertFileDoesNotExist(dirname($this->drawsDir) . '/evil_1.csv');
    }


    public function testResetRemovesOnlyDrawsOfCurrentSession(): void
    {
        $this->_dispatch('/adulte/1/quine/random', 'alice');
        $this->_dispatch('/enfant/1/quine/random', 'alice');
        $this->_dispatch('/adulte/1/quine/random', 'bob');

        $this->_dispatch('/reset', 'alice');

        $this->assertFileDoesNotExist($this->_drawsFile('adulte', '1', 'alice'));
        $this->assertFileDoesNotExist($this->_drawsFile('enfant', '1', 'alice'));
        $this->assertFileExists($this->_drawsFile('adulte', '1', 'bob'));
    }


    public function testResetWithoutSessionKeepsOtherSessionsDraws(): void
    {
        $this->_dispatch('/adulte/1/quine/random', 'bob');
        $this->_dispatch('/adulte/1/quine/random', '');

        $this->_dispatch('/reset', '');

        $this->assertFileDoesNotExist($this->_drawsFile('adulte', '1', ''));
        $this->assertFileExists($this->_drawsFile('adulte', '1', 'bob'));
    }
}
