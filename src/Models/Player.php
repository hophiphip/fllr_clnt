<?php

namespace App\Models;

use App\Traits\FromNamedArray;

/**
 *  This class contains player representation.
 */
class Player {
    use FromNamedArray;

    /**
     *  @var int $id player in game id
     */
    public int $id;

    /**
     *  @var string $color player current color
     */
    public string $color;

    /**
     *  Construct a new player.
     *
     *  @param int $id player id
     *  @param string $color player initial color
     *
     *  @return void
     */
    public function __construct(int $id, string $color) {
        $this->id = $id;
        $this->color = $color;
    }


    /**
     *  Return next player id.
     *
     *  @param int $currentPlayerId current player in game id
     *
     *  @return int
     */
    public static function nextPlayerId(int $currentPlayerId): int {
        if ($currentPlayerId != 1 && $currentPlayerId != 2) {
            return -1;
        }

        return ($currentPlayerId % 2) + 1;
    }
}
