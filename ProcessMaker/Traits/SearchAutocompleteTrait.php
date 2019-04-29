<?php
namespace ProcessMaker\Traits;

use DB;
use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessRequest;

trait SearchAutocompleteTrait
{
    public function search(Request $request)
    {
        $type = $request->input('type');
        $query = $request->input('filter');
        
        if (method_exists($this, camel_case("search $type"))) {
            $method = camel_case("search $type");
            $results = $this->$method($query);
            return response()->json($results);
        } else {
            return abort(404);
        }
    }
    
    private function searchRequester($query)
    {
        $results = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
            return function($query) use($expression) {
                $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
            };
        })->get();
        
        return $results->map(function ($user) {
            return $user->only(['id', 'username', 'firstname', 'lastname', 'avatar']);
        });
    }
    
    private function searchParticipants($query)
    {
        $results = [];
        
        $results['users'] = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
            return function($query) use($expression) {
                $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
            };
        })->get();

        $results['users'] = $results['users']->map(function ($user) {
            return $user->only(['id', 'username', 'firstname', 'lastname', 'avatar']);
        });        
        
        $results['groups'] = Group::pmql('name = "' . $query . '"', function($expression) {
            return function($query) use($expression) {
                $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
            };
        })->get();
        
        $results['groups'] = $results['groups']->map(function ($group) {
            return $group->only(['id', 'name']);
        });
        
        return $results;
    }

}