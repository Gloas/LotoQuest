<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Game;

use App\Domain\Game\Game;
use App\Domain\Game\GameNotFoundException;
use App\Domain\Game\GameRepository;

class InMemoryGameRepository implements GameRepository
{
    /**
     * @var Game[]
     */
    private array $users;

    /**
     * @param Game[]|null $users
     */
    public function __construct(?array $users = null)
    {
        $this->users = $users ?? [
            1 => new Game(1, '2026-01-17', 'adulte'),
            2 => new Game(2, '2026-01-17', 'enfant'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return array_values($this->users);
    }

    /**
     * {@inheritdoc}
     */
    public function findGameOfId(int $id): Game
    {
        if (!isset($this->users[$id])) {
            throw new GameNotFoundException();
        }

        return $this->users[$id];
    }
}
