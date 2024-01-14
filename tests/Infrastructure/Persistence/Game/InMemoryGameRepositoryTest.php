<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Game;

use App\Domain\Game\Game;
use App\Domain\Game\GameNotFoundException;
use App\Infrastructure\Persistence\Game\InMemoryGameRepository;
use Tests\TestCase;

class InMemoryGameTest extends TestCase
{
    public function testFindAll()
    {
        $game = new Game(1, '2024-12-11', 'adulte');

        $game_repository = new InMemoryGameRepository([1 => $game]);

        $this->assertEquals([$game], $game_repository->findAll());
    }


    public function testFindGameOfId()
    {
        $game = new Game(1, '2024-12-11', 'adulte');

        $game_repository = new InMemoryGameRepository([1 => $game]);

        $this->assertEquals($game, $game_repository->findGameOfId(1));
    }


    public function testFindGameOfIdThrowsNotFoundException()
    {
        $game_repository = new InMemoryGameRepository([]);
        $this->expectException(GameNotFoundException::class);
        $game_repository->findGameOfId(1);
    }
}
