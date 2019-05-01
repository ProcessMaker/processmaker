<?php
namespace ProcessMaker\Traits;

use DB;
use Auth;
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
    
    private function searchAll($query)
    {
        return [
            'status' => $this->searchStatus($query),
            'process' => $this->searchProcess($query),
            'requester' => $this->searchRequester($query),
            'participants' => $this->searchParticipants($query),
        ];
    }
    
    private function searchStatus()
    {
        return [
            ['name' => 'In Progress', 'value' => 'ACTIVE'],
            ['name' => 'Completed', 'value' => 'COMPLETED'],
            ['name' => 'Error', 'value' => 'ERROR'],
            ['name' => 'Canceled', 'value' => 'CANCELED'],
        ];
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
        $results = collect([]);
        $results->push(Auth::user());
    
        if (empty($query)) {
            $results = $results->merge(User::limit(49)->where('id', '!=', Auth::user()->id)->get());
        } else {
            $results = $results->merge(User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->where('id', '!=', Auth::user()->id)->limit(49)->get());
        }
        
        return $results->map(function ($user) {
            return $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        });  
    }
    
    private function searchParticipants($query)
    {
        //Initial array setup
        $results = [
            [
                'label' => 'Groups',
                'items' => [],
            ],
            [
                'label' => 'Users',
                'items' => [],
            ],
        ];
        
        if (empty($query)) {
            //Retrieve default list of 50 participants
            $results[0]['items'] = Group::limit(25)->get();
            $results[1]['items'] = User::limit(25)->get();
        }else {
            //Retrieve groups from database
            $results[0]['items'] = Group::pmql('name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();

            //Retrieve users from database
            $results[1]['items'] = User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();        
        }

        //Transform group array
        $results[0]['items'] = $results[0]['items']->map(function ($group) {
            $group = $group->only(['id', 'name']);
            $group['track'] = 'group-' . $group['id'];
            return $group;
        });

        //Transform user array
        $results[1]['items'] = $results[1]['items']->map(function ($user) {
            $user = $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
            $user['name'] = $user['fullname'];
            $user['track'] = 'user-' . $user['id'];
            unset($user['fullname']);
            return $user;
        });

        return $results;
    }

}