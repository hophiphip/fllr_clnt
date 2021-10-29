<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

use Illuminate\Support\Facades\Log;

use App\Models\Colors;

/**
 *  This class stores single game representation.
 */
class Game extends Model {
    /**
     *  @var string $collection contains collection name
     */
    protected $collection = 'game_collection';

    /**
     *  @var array $fillable contains collection fields names
     */
    protected $fillable = [
        'players',
        'field',
        'currentPlayerId',
        'winnerPlayerId',

        'stats'  
    ];

    /**
     *  Handle single game move 
     *      and return `true` in case of success
     *      otherwise return `false`.
     *
     *  @param string $_color player selected color
     *
     *  @return bool
     */
    public function handleMove(string $_color): bool {
        $color = strtolower($_color);

        $currentPlayerId = $this->currentPlayerId;
        $players = $this->players;
        $field = Field::fromArray($this->field);
        $stats = $this->stats;

        // Handle incorrect player moves
        if (
            // Players can't have same color
            Colors::compareColors(
                $color, 
                $players[($currentPlayerId % 2) + 1]['color']) ||
                
            // Player can't choose own color
            Colors::compareColors(
                $color, 
                $players[$currentPlayerId]['color']))
        {
            return false;
        }

        // update player color
        $players[$currentPlayerId]["color"] = $color;

        // NOTE: If a new cell is assignable then its ID is set to player id ->
        //  this way same cell won't be added twice. (Because cell's ID != 0).
        //
        // update cells field & stats : IDs
        for ($i = 0; $i < count($stats[$currentPlayerId]); $i++) {
            $cellIndex = $stats[$currentPlayerId][$i];

            // left 
            if (!($field->hasNoLeftCell($cellIndex))) {
                $leftIndex = $cellIndex - $field->width;
                if ($field->isAssignable($cellIndex, $leftIndex, $color)) { 
                    // assign other cell to current player id
                    $field->cells[$leftIndex]["playerId"] = $field->cells[$cellIndex]["playerId"];
                    // add other cell to current player cells
                    array_push($stats[$currentPlayerId], $leftIndex);
                }
            }

            // top
            if (!($field->hasNoTopCell($cellIndex))) {
                $topIndex = $cellIndex - $field->width + 1;
                if ($field->isAssignable($cellIndex, $topIndex, $color)) { 
                    // assign other cell to current player id
                    $field->cells[$topIndex]["playerId"] = $field->cells[$cellIndex]["playerId"];
                    // add other cell to current player cells
                    array_push($stats[$currentPlayerId], $topIndex);
                }
            }

            // right
            if (!($field->hasNoRightCell($cellIndex))) {
                $rightIndex = $cellIndex + $field->width;
                if ($field->isAssignable($cellIndex, $rightIndex, $color)) { 
                    // assign other cell to current player id
                    $field->cells[$rightIndex]["playerId"] = $field->cells[$cellIndex]["playerId"];
                    // add other cell to current player cells
                    array_push($stats[$currentPlayerId], $rightIndex);
                }
            }

            // bottom
            if (!($field->hasNoBottomCell($cellIndex))) {
                $bottomIndex = $cellIndex + $field->width - 1;
                if ($field->isAssignable($cellIndex, $bottomIndex, $color)) { 
                    // assign other cell to current player id
                    $field->cells[$bottomIndex]["playerId"] = $field->cells[$cellIndex]["playerId"];
                    // add other cell to current player cells
                    array_push($stats[$currentPlayerId], $bottomIndex);
                }
            }
        } 

        // update field : cells color
        foreach ($stats[$currentPlayerId] as $cellIndex) {
            $field->cells[$cellIndex]["playerId"] = $currentPlayerId; // Not needed, but whatever
            $field->cells[$cellIndex]["color"] = $color;
        }

        // update current player id : next player turn
        $currentPlayerId = (($currentPlayerId % 2) + 1);

        // SET
        // TODO: Too many too big updates --> can be improved --> update only specific field/subfields
        // TODO: Try using DB array methods -> push & pull (with 'stats')
        $this->currentPlayerId = $currentPlayerId;
        $this->players = $players;
        $this->field = $field;
        $this->stats = $stats;
        
        // update winner player id
        if (count($stats[$currentPlayerId]) / count($field->cells) > 0.5) {
            $this->winnerPlayerId = $currentPlayerId;
        }
        
        $this->save(); 

        return true;
    }
}
