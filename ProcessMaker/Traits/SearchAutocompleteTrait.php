<?php
namespace ProcessMaker\Traits;

use DB;
use Auth;
use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

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
        
        return $results->map(function ($process) {
            return $process->only(['id', 'name']);
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
    
    private function searchTaskAll($query)
    {
        return [
            'request' => $this->searchRequest($query),
            'name' => $this->searchName($query),
            'status' => $this->searchTaskStatus($query),
        ];
    }
    
    private function searchTaskStatus()
    {
        return [
            ['name' => 'In Progress', 'value' => 'ACTIVE'],
            ['name' => 'Completed', 'value' => 'CLOSED'],
        ];
    }
    
    private function searchName($query)
    {
        if (empty($query)) {
            $results = ProcessRequestToken::limit(50);
        } else {
            $results = ProcessRequestToken::pmql('element_name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            });
        }
        
        $tasks = $results->where('element_type', 'task')->get();
        
        $results = [];
        
        foreach ($tasks as $task) {
            $results[$task->element_name] = $task->element_name;
        }
        
        $return = [];
        
        foreach ($results as $result) {
            $return[] = ['name' => $result];
        }
        
        return $return;    
    }
    
    private function searchRequest($query)
    {
        if (empty($query)) {
            $results = ProcessRequest::limit(50)->get();
        } else {
            $results = ProcessRequest::pmql('name = "' . $query . '"', function($expression) {
                return function($query) use($expression) {
                    $query->where($expression->field->field(), 'LIKE',  '%' . $expression->value->value() . '%');
                };
            })->get();
        }
        
        return $results->map(function ($request) {
            return $request->only(['id', 'name']);
        });
    }
}