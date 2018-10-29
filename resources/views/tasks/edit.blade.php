@extends('layouts.layout')

@section('title')
    {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div id="task" class="container">
        <h1>{{$task->element_name}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="container-fluid">
                    <div class="card card-body">

                        <task-form process-id="{{$task->processRequest->process->getKey()}}"
                                   instance-id="{{$task->processRequest->getKey()}}"
                                   token-id="{{$task->getKey()}}"
                                   :form="{{json_encode($task->getForm()->config)}}"
                                   :data="{{json_encode($task->processRequest->data)}}"/>
                    </div>
                    <!-- end form /-->
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-header text-white bg-success">
                        <h4 style="margin:0; padding:0; line-height:1">{{$task['status']}}</h4>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class='far fa-calendar-alt'></i>
                            <small> {{__('Due in :day', ['day' => $task->due_at->diffForHumans()])}}</small>
                            <br>
                            {{$task->due_at->format(config('app.dateformat'))}}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Assigned To')}}</h5>
                            <img style="width:32px; height:32px" class="rounded-circle" src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif"> <span>Bob Worker</span>

                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small> {{__('Assigned :day', ['day' => $task->created_at->diffForHumans()])}}</small>
                            <br>
                            {{$task->created_at->format(config('app.dateformat'))}}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Request')}}</h5>
                            <a href="#">
                                #{{$task->process_request_id}} {{$task->process->name}}
                            </a>
                            <br><br>
                            <h5>{{__('Requested By')}}</h5>
                            <img style="width:32px; height:32px" class="rounded-circle" src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif"> <span>Joe Manager</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
    <script src="{{mix('js/tasks/show.js')}}"></script>
    <script>
        new Vue({
            el: '#task',
            data: {},
            mounted() {
            }
        });
    </script>
@endsection

@section('css')
@endsection
