<?php

namespace App\Models;

/**
 * This class stores single cell representation
 */
class Cell {
    /**
     *  @var int $playerId contains cell's owner id
     */
    public int $playerId;

    /**
     *  @var string $color contains cell's color
     */
    public string $color;

    /**
     *  Construct a new cell
     *
     *  @param int $playerId cell's initial player id
     *  @param string $color cell's initial color
     *
     *  @return void
     */
    public function __construct(int $playerId, string $color) {
        $this->playerId = $playerId;
        $this->color = $color;
    }
}
