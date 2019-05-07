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
        $task->processRequest->name =>
            Auth::user()->can('view', $task->processRequest) ? route('requests.show', ['id' => $task->processRequest->id]) : null,
        $task->element_name => null,
    ]])

    <div id="task" class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="container-fluid">

                    @if ($task->processRequest->status === 'ACTIVE')
                        @can('editData', $task->processRequest)
                            <ul id="tabHeader" role="tablist" class="nav nav-tabs">
                                <li class="nav-item"><a id="pending-tab" data-toggle="tab" href="#tab-form" role="tab"
                                                        aria-controls="tab-form" aria-selected="true"
                                                        class="nav-link active">{{__('Form')}}</a></li>
                                <li class="nav-item"><a id="summary-tab" data-toggle="tab" href="#tab-data" role="tab"
                                                        aria-controls="tab-data" aria-selected="false"
                                                        class="nav-link">{{__('Data')}}</a></li>
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
                                <small> {{__($dueLabels[$task->advanceStatus])}} @{{ moment(dateDueAt).fromNow() }}
                                </small>
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
                                @if ($task->advanceStatus === 'open')
                                <button class="btn btn-outline-secondary btn-block" @click="show">
                                    <i class="fas fa-user-friends"></i> {{__('Reassign')}}
                                </button>
                                @endif
                                <b-modal v-model="showReassignment" size="md" centered title="{{__('Reassign to')}}" @hide="cancelReassign"
                                         v-cloak>
                                    <div class="form-group">
                                        {!!Form::label('user', __('User'))!!}
                                        <multiselect v-model="selectedUser"
                                                     placeholder="{{__('Select the user to reassign to the task')}}"
                                                     :options="usersList"
                                                     :multiple="false"
                                                     track-by="fullname"
                                                     :show-labels="false"
                                                     :searchable="false"
                                                     :internal-search="false"
                                                     @search-change="loadUsers"
                                                     label="fullname">

                                            <template slot="tag" slot-scope="props">
                                                <span class="multiselect__tag  d-flex align-items-center"
                                                      style="width:max-content;">
                                                    <span class="option__desc mr-1">
                                                        <span class="option__title">@{{ props.option.fullname }}</span>
                                                    </span>
                                                    <i aria-hidden="true" tabindex="1"
                                                       @click="props.remove(props.option)"
                                                       class="multiselect__tag-icon"></i>
                                                </span>
                                            </template>

                                            <template slot="option" slot-scope="props">
                                                <div class="option__desc d-flex align-items-center">
                                                    <span class="option__title mr-1">@{{ props.option.fullname }}</span>
                                                </div>
                                            </template>
                                        </multiselect>
                                    </div>
                                    <div slot="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" @click="cancelReassign">
                                            {{__('Cancel')}}
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" @click="reassignUser"
                                                :disabled="disabled">
                                            {{__('Reassign')}}
                                        </button>
                                    </div>
                                </b-modal>
                            </span>
                                    </div>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-calendar-alt"></i>
                                <small> {{__('Assigned') }} @{{ moment(createdAt).fromNow() }}</small>
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
                                <avatar-image v-if="userRequested" size="32"
                                              class="d-inline-flex pull-left align-items-center"
                                              :input-data="userRequested"></avatar-image>
                                <p v-if="!userRequested">{{__('Webhook')}}</p>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/tasks/show.js')}}"></script>
    <script>
      new Vue({
        el: '#task',
        data: {
          //Edit data
          fieldsToUpdate: [],
          jsonData: "",
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
          userRequested: [],
          selectedUser: []
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
          },
          disabled() {
            return this.selectedUser ? this.selectedUser.length === 0 : true;
          }
        },
        methods: {
          // Data editor
          updateRequestData() {
            const data = JSON.parse(this.jsonData);
            ProcessMaker.apiClient
              .put("requests/" + this.task.process_request_id, {
                data: data,
                task_element_id: this.task.element_id,
              })
              .then(response => {
                this.fieldsToUpdate.splice(0);
                ProcessMaker.alert("{{__('The request data was saved.')}}", "success");
              });
          },
          saveJsonData() {
            try {
              const value = JSON.parse(this.jsonData);
              this.updateRequestData();
            } catch (e) {
              // Invalid data
            }
          },
          editJsonData() {
            this.jsonData = JSON.stringify(this.data, null, 4);
          },
          // Reassign methods
          show() {
            this.showReassignment = true;
          },
          cancelReassign() {
            this.showReassignment = false;
            this.selectedUser = [];
          },
          reassignUser() {
            if (this.selectedUser) {
              console.log(this.selectedUser);
              ProcessMaker.apiClient
                .put("tasks/" + this.task.id, {
                  user_id: this.selectedUser.id
                })
                .then(response => {
                  this.showReassignment = false;
                  this.selectedUser = [];
                  window.location.href =
                    "/requests/" + response.data.process_request_id;
                });
            }
          },
          loadUsers(filter) {
            filter = typeof filter === 'string' ? '?filter=' + filter + '&' : '?';
            ProcessMaker.apiClient
              .get(
                "tasks/" + this.task.id + filter, {
                  params: {
                    include: "assignableUsers"
                  }
                }
              )
              .then(response => {
                this.usersList = response.data.assignableUsers;
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
          this.editJsonData();
        }
      });
    </script>
@endsection

@section('css')
    <style>
        .inline-input {
            margin-right: 6px;
        }

        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }

        .input-and-select {
            width: 212px;
        }

        .multiselect__element span img {
            border-radius: 50%;
            height: 20px;
        }

        .multiselect__tags-wrap {
            display: flex !important;
        }

        .multiselect__tags-wrap img {
            height: 15px;
            border-radius: 50%;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection