<?php

namespace ProcessMaker\Traits;

use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

trait SearchAutocompleteTrait
{
    public function search(Request $request)
    {
        $type = $request->input('type');
        $query = $request->input('filter');

        if (method_exists($this, Str::camel("search $type"))) {
            $method = Str::camel("search $type");
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
            ['name' => __('In Progress'), 'value' => 'In Progress'],
            ['name' => __('Completed'), 'value' => 'Completed'],
            ['name' => __('Error'), 'value' => 'Error'],
            ['name' => __('Canceled'), 'value' => 'Canceled'],
        ];
    }

    private function searchProcess($query)
    {
        if (empty($query)) {
            $results = Process::nonSystem()->limit(50)->get();
        } else {
            $results = Process::nonSystem()->pmql('name = "' . $query . '"', function ($expression) {
                return function ($query) use ($expression) {
                    $query->where($expression->field->field(), 'LIKE', '%' . $expression->value->value() . '%');
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

        if (Auth::user()->can('view-users')) {
            if (empty($query)) {
                $results = $results->merge(User::limit(49)->where('id', '!=', Auth::user()->id)->get());
            } else {
                $results = $results->merge(User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function ($expression) {
                    return function ($query) use ($expression) {
                        $query->where($expression->field->field(), 'LIKE', '%' . $expression->value->value() . '%');
                    };
                })->where('id', '!=', Auth::user()->id)->limit(49)->get());
            }
        }

        return $results->map(function ($user) {
            return $user->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        });
    }

    private function searchParticipants($query)
    {
        $results = collect([]);
        $results->push(Auth::user());

        if (Auth::user()->can('view-users')) {
            if (empty($query)) {
                $results = $results->merge(User::limit(49)->where('id', '!=', Auth::user()->id)->get());
            } else {
                $results = $results->merge(User::pmql('username = "' . $query . '" OR firstname = "' . $query . '"  OR lastname = "' . $query . '"', function ($expression) {
                    return function ($query) use ($expression) {
                        $query->where($expression->field->field(), 'LIKE', '%' . $expression->value->value() . '%');
                    };
                })->where('id', '!=', Auth::user()->id)->limit(49)->get());
            }
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
            ['name' => __('Self Service'), 'value' => 'Self Service'],
            ['name' => __('In Progress'), 'value' => 'In Progress'],
            ['name' => __('Completed'), 'value' => 'Completed'],
        ];
    }

    private function searchName($query)
    {
        if (empty($query)) {
            $results = ProcessRequestToken::limit(50);
        } else {
            $results = ProcessRequestToken::pmql('element_name = "' . $query . '"', function ($expression) {
                return function ($query) use ($expression) {
                    $query->where($expression->field->field(), 'LIKE', '%' . $expression->value->value() . '%');
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
            $results = ProcessRequest::pmql('name = "' . $query . '"', function ($expression) {
                return function ($query) use ($expression) {
                    $query->where($expression->field->field(), 'LIKE', '%' . $expression->value->value() . '%');
                };
            })->get();
        }

        return $results->map(function ($request) {
            return $request->only(['id', 'name']);
        });
    }
}
