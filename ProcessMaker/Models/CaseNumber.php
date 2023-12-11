<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class to generate a case number.
 *
 * For example to generate a unique id for a process request that is a parent process
 * and non system. E.g.
 *
 * $sequence = CaseNumber::generate($request->id);
 */
class CaseNumber extends Model
{
    use HasFactory;

    protected $fillable = ['process_request_id'];

    /**
     * Generate a unique sequence for a given name.
     *
     * @param int|string $id of the request
     * @return int The next value in the sequence.
     */
    public static function generate($requestId): int
    {
        // Create a new sequence with the given name
        $sequence = self::create(['process_request_id' => $requestId]);

        // Return the id of the sequence as the next value in the sequence
        return $sequence->id;
    }
}
