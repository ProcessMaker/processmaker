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
     $temp = json_decode(file_get_contents(resource_path(). '/js/data/timeszones.json'), true);
      $timezones=[];
      foreach($temp['data'] as $t){
        $timezones[$t['value']] = $t['content'];
      }
      return $timezones;
     }

     public static function states(){
      return json_decode(file_get_contents(resource_path(). '/js/data/states_hash.json'), true);
     }

     public static function countries(){
      return json_decode(file_get_contents(resource_path(). '/js/data/countries.json'), true);
     }
}
