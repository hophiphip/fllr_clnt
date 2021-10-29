<?php

namespace App\Models;

use Exception;

/**
 *  This class provides helpful methods for working with colors.
 */
final class Colors {
    /**
     *  @var string $colorsRegex is the regex string that matches all colors from $colorsTable (case-insensitive)
     */
    public static string $colorsRegex = '/^#([0-9a-f]{6})$|^red$|^green$|^blue$|^yellow$|^magenta$|^cyan$|^white$/i';

    /**
     *  @var array $colorsTable is a simple colors table to help to compare different representations of colors
     */
    public static array $colorsTable = array(
        '#ff0000' => 0,
        '#00ff00' => 1,
        '#0000ff' => 2,
        '#ffff00' => 3,
        '#ff00ff' => 4,
        '#00ffff' => 5,
        '#ffffff' => 6,

            'red' => 0,
          'green' => 1,
           'blue' => 2,
         'yellow' => 3,
        'magenta' => 4,
           'cyan' => 5,
          'white' => 6,
    );

    /**
     * @var array $colors is a color-id to color table
     */
    public static array $colors = array(
        0 => '#ff0000',
        1 => '#00ff00',
        2 => '#0000ff',
        3 => '#ffff00',
        4 => '#ff00ff',
        5 => '#00ffff',
        6 => '#ffffff',
    );

    /**
     *  Returns one random color string
     *
     * @return string
     * @throws Exception
     */
    public static function randomColorString(): string {
        return self::$colors[random_int(0, count(self::$colors) - 1)];
    }

    /**
     *  Returns a shuffled array of non-repeating colors.
     *
     *  @return array
     */
    public static function shuffledColors(): array {
        $out = self::$colors;

        shuffle($out);
        return $out;
    }

    /**
     *  Compares colors. Supports different color representations and is case-insensitive.
     *  Returns 'true' is colors are the same, otherwise 'false'
     *
     *  @param string $_r first color to compare
     *  @param string $_l second color to compare
     *
     *  @return bool
     */
    public static function compareColors(string $_r, string $_l): bool {
        $r = strtolower($_r);
        $l = strtolower($_l);
        if (!array_key_exists($r, self::$colorsTable) || !array_key_exists($l, self::$colorsTable))
            return false;

        return self::$colorsTable[$r] == self::$colorsTable[$l];
    }

    /**
     *  Returns allowed color for player next move.
     *
     * @param string $color1
     * @param string $color2
     *
     * @return string|null
     */
    public static function allowedColor(string $color1, string $color2): ?string
    {
        if ($color1 == null || $color2 == null) {
            return null;
        }

        foreach(Colors::$colors as $color) {
            if (Colors::compareColors($color1, $color) == false &&
                Colors::compareColors($color2, $color) == false) {
                return $color;
            }
        }

        return null;
    }
}
