<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

use App\Models\Cell;

// NOTE: A workaround for issue:
//          https://github.com/jenssegers/laravel-mongodb/issues/1059

trait FromNamedArray {
    /**
     * Convert named array to class(model) object
     *
     * @param array $data named array that contains class fields
     * @return self
     */
    public static function fromArray(array $data = []): static
    {
        foreach (get_object_vars($object = new self) as $property => $default) {
            if (!array_key_exists($property, $data))
                continue;

            class_uses(Cell::class, true);

            $object->{$property} = $data[$property];
        }

        return $object;
    }
}
