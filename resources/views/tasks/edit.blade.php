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
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div :class="statusCard">
                        <h4 style="margin:0; padding:0; line-height:1">{{__($task->advanceStatus)}}</h4>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class='far fa-calendar-alt'></i>
                            <small> {{__('Due in :day', ['day' => $task->due_at->diffForHumans()])}}</small>
                            <br>
                            {{$task->due_at->format(config('app.dateformat'))}}
                        </li>
                        <li class="list-group-item align-center">
                            <h5>{{__('Assigned To')}}</h5>
                            <avatar-image size="32" class="d-flex pull-left align-items-center"
                                          :input-data="userAssigned"></avatar-image>
                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small> {{__('Assigned :day', ['day' => $task->created_at->diffForHumans()])}}</small>
                            <br>
                            {{$task->created_at->format(config('app.dateformat'))}}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Request')}}</h5>
                            <a href="{{route('requests.show', [$task->process_request_id])}}">
                                #{{$task->process_request_id}} {{$task->process->name}}
                            </a>
                            <br><br>
                            <h5>{{__('Requested By')}}</h5>
                            <avatar-image size="32" class="d-flex pull-left align-items-center"
                                          :input-data="userRequested"></avatar-image>
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
            data: {
                task: @json($task),
                assigned: @json($task->user),
                requested: @json($task->processRequest->user),
                statusCard: 'card-header text-capitalize text-white bg-success',
                userAssigned: [],
                userRequested: []
            },
            methods: {
                classHeaderCard(status) {
                    let header = 'bg-success';
                    switch (status) {
                        case 'closed':
                            header = 'bg-secondary';
                            break;
                        case 'overdue':
                            header = 'bg-danger';
                            break;
                    }
                    return 'card-header text-capitalize text-white ' + header;
                },
                assignedUserAvatar(user) {
                    return [{
                        src: user.avatar,
                        name: user.fullname
                    }];
                }
            },
            mounted() {
                this.statusCard = this.classHeaderCard(this.advanceStatus)
                this.userAssigned = this.assignedUserAvatar(this.assigned)
                this.userRequested = this.assignedUserAvatar(this.requested)
            }
        });
    </script>
@endsection

@section('css')
@endsection
