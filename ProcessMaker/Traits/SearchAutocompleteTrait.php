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
            [
                'label' => 'Users',
                'items' => [],
            ],
            [
                'label' => 'Groups',
                'items' => [],
            ],
        ];
        
        if (empty($query)) {
            $results[1]['items'] = Group::limit(25)->get();
            $results[0]['items'] = User::limit(25)->get();
        }else {
            $results[1]['items'] = Group::pmql('name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();

            $results[0]['items'] = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();        
        }

        $results[0]['items'] = $results[0]['items']->map(function ($user) {
            $user = $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
            $user['name'] = $user['fullname'];
            $user['track'] = 'user-' . $user['id'];
            unset($user['fullname']);
            return $user;
        });

        $results[1]['items'] = $results[1]['items']->map(function ($group) {
            $group = $group->only(['id', 'name']);
            $group['track'] = 'group-' . $group['id'];
            return $group;
        });

        return $results;
    }

}