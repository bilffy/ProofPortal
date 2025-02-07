<?php

namespace App\Models;

use DateTimeInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    // public function setAttribute($key, $value)
    // {
    //     if ($value instanceof CarbonInterface) {
    //         // Convert all carbon dates to UTC upon saving
    //         $value = $value->clone()->setTimezone('UTC');
    //     } else if ($value instanceof DateTimeInterface) {
    //         // Convert all other dates to timestamps
    //         $value = $value->getTimestamp();
    //     }
    //     // They will be reconverted to a Carbon instance but with the correct timezone
    //     return parent::setAttribute($key, $value);
    // }
}