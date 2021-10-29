<?php

namespace App\Models;

use App\Traits\FromNamedArray;
use Exception;
use JetBrains\PhpStorm\Pure;
use SplFixedArray;

/**
 *  This class stores single game field representation.
 */
class Field {
    use FromNamedArray;

    /**
     *  @var int $width contains field's width
     */
    public int $width;

    /**
     *  @var int $height contains field's height
     */
    public int $height;

    /**
     *  @var SplFixedArray|array $cells contains field's cells
     */
    public SplFixedArray|array $cells = array();

    /**
     *  Construct a new field.
     *
     * @param int $width field width
     * @param int $height field height
     *
     * @return void
     * @throws Exception
     */
    public function __construct(int $width = 0, int $height = 0) {
        $this->width = $width;
        $this->height = $height;

        // Field structure:
        //
        // NOTE: width  -> any positive number
        //       height -> odd positive number

        // o o o o
        //  o o o
        // o o o o
        //  o o o
        // o o o o
        //
        $this->cells = new SplFixedArray($this->width * $this->height - intdiv($this->height, 2));

        for ($i = 0; $i < count($this->cells); $i++) {
            $this->cells[$i] = new Cell(0, Colors::randomColorString());
        }
    }

    /**
     *  Validates cell `x` and `y` coordinates.
     *
     *  @param int $y cell y coordinate
     *  @param int $x cell x coordinate
     *
     *  @return bool
     */
    public function isValidCell(int $y, int $x): bool {
        return ($x >= 0 && $x < $this->width) &&
               ($y >= 0 && $y < $this->height);
    }

    /**
     *  Validates cell index.
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    public function isValidIndex(int $i): bool {
        return $i >= 0 && $i < count($this->cells);
    }

    /**
     * Checks if cell is at the left edge of the field.
     *  Leftmost cells
     *      x o o o
     *       o o o
     *      x o o o
     *       o o o
     *      x o o o
     *
     *  @param int $i cell index
     *
     *  @return bool
    */
    public function isInLeftCorner(int $i): bool {
        return ($i % (2 * $this->width - 1)) == 0;
    }


    /**
     * Checks if cell is at the top edge of the field.
     *  Top cells
     *      x x x x
     *       o o o
     *      o o o o
     *       o o o
     *      o o o o
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    public function isInTopCorner(int $i): bool {
        return $i < $this->width;
    }


    /**
     * Checks if cell is at the right edge of the field.
     *  Rightmost cells
     *      o o o x
     *       o o o
     *      o o o x
     *       o o o
     *      o o o x
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    public function isInRightCorner(int $i): bool {
        // TODO: Might have some issues with negative numbers
        return (($i - $this->width + 1) % (2 * $this->width - 1)) == 0;
    }


    /**
     * Checks if cell is at the bottom edge of the field.
     *  Bottom cells
     *      o o o o
     *       o o o
     *      o o o o
     *       o o o
     *      x x x x
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    public function isInBottomCorner(int $i): bool {
        $cellCount = count($this->cells);
        return ($i < $cellCount) &&
               ($i >= ($cellCount - $this->width));
    }


    /*
     *  NOTE: Cell corners explained:
     *    [right]      [top]
     *            c
     *  [bottom]     [left]
     *
     */

    /**
     * Checks if cell has existing bottom cell neighbour.
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    #[Pure] public function hasNoBottomCell(int $i): bool {
        return $this->isInLeftCorner($i) ||
               $this->isInBottomCorner($i);
    }

    /**
     * Checks if cell has existing left cell neighbour.
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    #[Pure] public function hasNoLeftCell(int $i): bool {
        return $this->isInLeftCorner($i) ||
               $this->isInTopCorner($i);
    }

    /**
     * Checks if cell has existing top cell neighbour.
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    #[Pure] public function hasNoTopCell(int $i): bool {
        return $this->isInTopCorner($i) ||
               $this->isInRightCorner($i);
    }

    /**
     * Checks if cell has existing right cell neighbour.
     *
     *  @param int $i cell index
     *
     *  @return bool
     */
    #[Pure] public function hasNoRightCell(int $i): bool {
        return $this->isInRightCorner($i) ||
               $this->isInBottomCorner($i);
    }

    /**
     * Checks if `other` cell (neighbour cell of `current` cell)
     *  doesn't belong to player stats cells.
     *
     * @param int $index cell index
     *
     * @return bool
     */
    public function isNotPlayerCell(int $index): bool {
        return $this->isValidIndex($index) &&
               0 == $this->cells[$index]["playerId"];
    }

    /**
     * Checks if `other` cell (neighbour cell of `current` cell)
     *  can be added to player owned cells.
     *
     *  @param int $currentIndex current cell index
     *  @param int $otherIndex other cell index
     *  @param string $playerColor current player color
     *
     *  @return bool
     */
    public function isAssignable(int $currentIndex, int $otherIndex, string $playerColor): bool {
        return $this->isValidIndex($currentIndex) &&
               $this->isNotPlayerCell($otherIndex) &&
               Colors::compareColors($playerColor, $this->cells[$otherIndex]["color"]);
    }
}
