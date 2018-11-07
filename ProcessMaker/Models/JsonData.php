<?php

namespace ProcessMaker\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Application Data
 *
 * @package ProcessMaker\Models
 */
class JsonData extends Model
{
     public static function timezones(){
         return json_decode(file_get_contents(resource_path(). '/js/data/timeszones.json'), false);
     }

     public static function states(){
      return json_decode(file_get_contents(resource_path(). '/js/data/states_hash.json'), true);
     }

     public static function countries(){
      return json_decode(file_get_contents(resource_path(). '/js/data/countries.json'), true);
     }

    public static function datetimeFormats(){
        return json_decode(file_get_contents(resource_path(). '/js/data/datetime_formats.json'), true);
    }
}
