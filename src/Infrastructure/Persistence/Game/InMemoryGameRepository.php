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
    public function __construct(array $users = null)
    {
        $this->users = $users ?? [
            1 => new Game(1, 'bill.gates', 'Bill', 'Gates'),
            2 => new Game(2, 'steve.jobs', 'Steve', 'Jobs'),
            3 => new Game(3, 'mark.zuckerberg', 'Mark', 'Zuckerberg'),
            4 => new Game(4, 'evan.spiegel', 'Evan', 'Spiegel'),
            5 => new Game(5, 'jack.dorsey', 'Jack', 'Dorsey'),
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
