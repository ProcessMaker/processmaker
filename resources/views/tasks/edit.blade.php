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
    @include('shared.breadcrumbs', ['routes' => [
        __('Tasks') => route('tasks.index'),
        function() use ($task) {
            if ($task->advanceStatus == 'completed') {
                return ['Completed Tasks', route('tasks.index', ['status' => 'CLOSED'])];
            }
            return ['To Do Tasks', route('tasks.index')];
        },
        $task->processRequest->name => route('requests.show', ['id' => $task->processRequest->id]),
        $task->element_name => null,
    ]])

    <div id="task" class="container">
        
        <div class="row">
            <div class="col-md-8">
                <div class="container-fluid">

                    @if ($task->processRequest->status === 'ACTIVE')
                        @can('editData', $task->processRequest)
                            <ul id="tabHeader" role="tablist" class="nav nav-tabs">
                                <li class="nav-item"><a id="pending-tab" data-toggle="tab" href="#tab-form" role="tab" aria-controls="tab-form" aria-selected="true" class="nav-link active">{{__('Form')}}</a></li>
                                <li class="nav-item"><a id="summary-tab" data-toggle="tab" href="#tab-data" role="tab" aria-controls="tab-data" aria-selected="false" class="nav-link">{{__('Data')}}</a></li>
                            </ul>
                        @endcan
                    @endif
                    <div id="tabContent" class="tab-content">
                        <div id="tab-form" role="tabpanel" aria-labelledby="tab-form" class="tab-pane active show">
                            @if ($task->getScreen() && ($task->advanceStatus==='open' || $task->advanceStatus==='overdue'))
                            <div class="card card-body">
                                <task-screen process-id="{{$task->processRequest->process->getKey()}}"
                                           instance-id="{{$task->processRequest->getKey()}}"
                                           token-id="{{$task->getKey()}}"
                                           :screen="{{json_encode($task->getScreen()->config)}}"
                                           :computed="{{json_encode($task->getScreen()->computed)}}"
                                           :custom-css="{{json_encode(strval($task->getScreen()->custom_css))}}"
                                           :data="{{json_encode($task->processRequest->data, JSON_FORCE_OBJECT)}}"/>
                            </div>
                            @elseif ($task->advanceStatus==='completed')
                            <div class="card card-body" align="center">
                                <h1>Task Completed <i class="fas fa-clipboard-check"></i></h1>
                            </div>
                            @endif
                        </div>
                        @if ($task->processRequest->status === 'ACTIVE')
                        @can('editData', $task->processRequest)
                        <div id="tab-data" role="tabpanel" aria-labelledby="tab-data" class="tab-pane">
                            @include('tasks.editdata')
                        </div>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
                            @if(!empty($task->getDefinition()['allowReassignment']) && $task->getDefinition()['allowReassignment']==='true')
                            <div>
                            <br>
                            <span>
                                <button class="btn btn-outline-secondary btn-block" @click="show">
                                    <i class="fas fa-user-friends"></i> {{__('Reassign')}}
                                </button>
                                <b-modal v-model="showReassignment" size="md" centered title="{{__('Reassign to')}}" v-cloak>
                                    <div class="list-users-groups" style="overflow: auto; height:100px">
                                        <span
                                            v-for="(row, index) in usersList"
                                            class="list-group-item list-group-item-action pt-1 pb-1"
                                            :class="{'bg-primary': selectedIndex == index}"
                                            @click="selectedItem(row, index)"
                                            @dblclick="selectedItem(row, index);reassignUser();"
                                            >
                                            <avatar-image class-container size="12" class-image :input-data="row"></avatar-image>
                                        </span>
                                    </div>
                                    <div slot="modal-footer">
                                            <b-button @click="cancelReassign" class="btn btn-outline-success btn-sm text-uppercase">{{__('Cancel')}}</b-button>
                                        <b-button
                                            :disabled="selectedIndex < 0"
                                            @click="reassignUser"
                                            class="btn btn-success btn-sm text-uppercase"
                                            >{{__('Reassign')}}</b-button>
                                    </div>
                                </b-modal>
                            </span>
                            </div>
                            @endif
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
                //Edit data
                fieldsToUpdate: [],
                jsonData: "",
                selectedData: '',
                monacoLargeOptions: {
                    automaticLayout: true,
                },
                showJSONEditor: false,

                // Reassignment
                selected: null,
                selectedIndex: -1,
                usersList: [],
                filter: "",
                showReassignment: false,

                task: @json($task),
                assigned: @json($task->user),
                requested: @json($task->processRequest->user),
                data: @json($task->processRequest->data),
                statusCard: 'card-header text-capitalize text-white bg-success',
                userAssigned: [],
                userRequested: []
            },
            watch: {
                showReassignment(show) {
                    show ? this.loadUsers() : null;
                }
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
                // Data editor
                updateRequestData() {
                    const data = {};
                    this.fieldsToUpdate.forEach(name=>{
                        data[name] = this.data[name];
                    });
                    ProcessMaker.apiClient
                        .put("requests/" + this.task.process_request_id, {
                            data: data
                        })
                        .then(response => {
                            this.fieldsToUpdate.splice(0);
                            ProcessMaker.alert("{{__('Request data successfully updated')}}", "success");
                        });
                },
                updateData(name, value) {
                    if (name) {
                        this.$set(this.data, name, value);
                        this.fieldsToUpdate.indexOf(name) === -1 ? this.fieldsToUpdate.push(name) : null;
                    }
                },
                closeJsonData() {
                    this.selectedData = '';
                    this.showJSONEditor = false;
                },
                saveJsonData() {
                    try{
                        if (this.selectedData) {
                            const value = JSON.parse(this.jsonData);
                            this.$set(this.data, this.selectedData, value);
                            this.showJSONEditor = false;
                            this.fieldsToUpdate.indexOf(this.selectedData) === -1 ? this.fieldsToUpdate.push(this.selectedData) : null;
                            this.updateRequestData();
                        }
                    } catch (e) {
                    }
                },
                editJsonData(name) {
                    if (this.data[name] !== undefined) {
                        this.selectedData = name;
                        this.jsonData = JSON.stringify(this.data[name], null, 4);
                        this.showJSONEditor = true;
                    }
                },

                // Reassign methods
                show() {
                    this.showReassignment = true;
                },
                cancelReassign() {
                    this.showReassignment = false;
                    this.selectedItem(null, -1);
                },
                reassignUser() {
                    if (this.selected) {
                        ProcessMaker.apiClient
                                .put("tasks/" + this.task.id, {
                                    user_id: this.selected.id
                                })
                                .then(response => {
                                    this.showReassignment = false;
                                    this.selectedItem(null, -1);
                                    window.location.href =
                                            "/requests/" + response.data.process_request_id;
                                });
                    }
                },
                selectedItem(selected, index) {
                    this.selected = selected;
                    this.selectedIndex = index;
                },
                loadUsers() {
                    ProcessMaker.apiClient.get("tasks/" + this.task.id, {
                        params: {
                            include: "assignableUsers"
                        }
                    }).then(response => {
                        this.$set(this, "usersList", response.data.assignableUsers);
                    });
                },

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
                this.updateRequestData = debounce(this.updateRequestData, 1000);
            }
        });
    </script>
@endsection

@section('css')
<style>
    .list-users-groups {
        border: 1px solid #b6bfc6;
        border-radius: 2px;
        height: 10em;
    }
</style>
@endsection
