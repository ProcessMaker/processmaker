@extends('layouts.layout')

@section('meta')
<meta name="request-id" content="{{ $task->processRequest->id }}">
@endsection

@section('title')
    {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_task')])
@endsection

@section('content')
    <div id="task" class="container">
        <h1>{{$task->element_name}}</h1>
        <div class="row">
            @if ($task->getScreen() && ($task->advanceStatus==='open' || $task->advanceStatus==='overdue'))
            <div class="col-8">
                <div class="container-fluid">
                    <div class="card card-body">
                        <task-screen process-id="{{$task->processRequest->process->getKey()}}"
                                   instance-id="{{$task->processRequest->getKey()}}"
                                   token-id="{{$task->getKey()}}"
                                   :screen="{{json_encode($task->getScreen()->config)}}"
                                   :data="{{json_encode($task->processRequest->data)}}"/>
                    </div>
                </div>
            </div>
            @elseif ($task->advanceStatus==='completed')
            <div class="col-8">
                <div class="container-fluid">
                    <div class="card card-body" align="center">
                        <h1>Task Completed <i class="fas fa-clipboard-check"></i></h1>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-4">
                <template v-if="dateDueAt">
                <div class="card">
                    <div :class="statusCard">
                        <h4 style="margin:0; padding:0; line-height:1">{{__($task->advanceStatus)}}</h4>
                    </div>
                    <ul class="list-group list-group-flush w-100">
                        <li class="list-group-item">
                            <i class='far fa-calendar-alt'></i>
                            <small> {{__($dueLabels[$task->advanceStatus])}}  @{{ moment(dateDueAt).fromNow() }}</small>
                            <br>
                            @{{ moment(dateDueAt).format() }}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Assigned To')}}</h5>
                            <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                      :input-data="userAssigned"></avatar-image>
                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small> {{__('Assigned') }}  @{{ moment(createdAt).fromNow() }}</small>
                            <br>
                            @{{ moment(createdAt).format() }}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Request')}}</h5>
                            <a href="{{route('requests.show', [$task->process_request_id])}}">
                                #{{$task->process_request_id}} {{$task->process->name}}
                            </a>
                            <br><br>
                            <h5>{{__('Requested By')}}</h5>
                            <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                      :input-data="userRequested"></avatar-image>
                        </li>
                    </ul>
                </div>
                </template>
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
            computed: {
                dateDueAt() {
                    return this.task.due_at;
                },
                createdAt() {
                    return this.task.created_at;
                }
            },
            methods: {
                classHeaderCard(status) {
                    let header = 'bg-success';
                    switch (status) {
                        case 'completed':
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
                this.statusCard = this.classHeaderCard(this.task.advanceStatus)
                this.userAssigned = this.assigned
                this.userRequested = this.requested
                ProcessMaker.removeNotifications([], [document.location.pathname]);
            }
        });
    </script>
@endsection

@section('css')
@endsection
