<?php
namespace ProcessMaker\Traits;

use DB;
use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;

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
    
    private function searchProcess($query)
    {
        if (empty($query)) {
            $results = Process::limit(50)->get();
        } else {
            $results = Process::pmql('name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();
        }
        
        return $results->map(function ($user) {
            return $user->only(['id', 'name']);
        });
    }
    
    private function searchRequester($query)
    {
        if (empty($query)) {
            $results = User::limit(50)->get();
        } else {
            $results = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();
        }
        return $results->map(function ($user) {
            return $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        });  
    }
    
    private function searchParticipants($query)
    {
        $results = [
            'users' => [
                'label' => 'Users',
                'items' => [],
            ],
            'groups' => [
                'label' => 'Groups',
                'items' => [],
            ],
        ];
        
        if (empty($query)) {
            $results['users']['items'] = User::limit(50)->get();
            $results['groups']['items'] = Group::limit(50)->get();
        }else {
            $results['users']['items'] = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();        
            
            $results['groups']['items'] = Group::pmql('name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();
        }

        $results['users']['items'] = $results['users']['items']->map(function ($user) {
            $user = $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
            $user['name'] = $user['fullname'];
            unset($user['fullname']);
            return $user;
        });

        $results['groups']['items'] = $results['groups']['items']->map(function ($group) {
            return $group->only(['id', 'name']);
        });

        return $results;
    }

}