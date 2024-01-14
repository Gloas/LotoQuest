<?php

declare(strict_types=1);

namespace App\Domain\Game;

use JsonSerializable;

class Game implements JsonSerializable
{
    private ?int $id;

    private string $date;

    private string $type;


    public function __construct(?int $id, string $date, string $type)
    {
        $this->id = $id;
        $this->date = $date;
        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getType(): string
    {
        return $this->type;
    }


    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'type' => $this->type
        ];
    }
}
