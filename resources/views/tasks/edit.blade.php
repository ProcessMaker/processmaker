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

@section('breadcrumbs')
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
@endsection
@section('content')
    <div id="task" class="container-fluid px-3">
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div class="container-fluid h-100 d-flex flex-column">
                    @if ($task->processRequest->status === 'ACTIVE')
                        @can('editData', $task->processRequest)
                            <ul id="tabHeader" role="tablist" class="nav nav-tabs">
                                <li class="nav-item"><a id="pending-tab" data-toggle="tab" href="#tab-form" role="tab"
                                                        aria-controls="tab-form" aria-selected="true"
                                                        class="nav-link active">{{__('Form')}}</a></li>
                                <li class="nav-item"><a id="summary-tab" data-toggle="tab" href="#tab-data" role="tab"
                                                        aria-controls="tab-data" aria-selected="false"
                                                        @click="resizeMonaco"
                                                        class="nav-link">{{__('Data')}}</a></li>
                            </ul>
                        @endcan
                    @endif
                    <div id="tabContent" class="tab-content flex-grow-1">
                        <div id="tab-form" role="tabpanel" aria-labelledby="tab-form" class="tab-pane active show h-100">
                            @if ($task->advanceStatus==='open' || $task->advanceStatus==='overdue')
                                <div class="card card-body border-top-0 h-100">
                                    @if ($task->getScreen())
                                      @if ($task->getScreen()->type === 'FORM (ADVANCED)')
                                        <advanced-screen-frame
                                          :allow-interstitial="allowInterstitial"
                                          @task-completed="redirectWhenTaskCompleted"
                                          :config="{{json_encode($task->getScreen()->config)}}"
                                          :csrf-token="'{{ csrf_token() }}'"
                                          :submiturl="'{{$submitUrl}}'"
                                          token-id="{{$task->getKey()}}"
                                          :data="{{$task->processRequest->data ? json_encode($task->processRequest->data) : '{}'}}"
                                        >
                                        </advanced-screen-frame>
                                      @else
                                        <task-screen
                                            ref="taskScreen"
                                            :listen-process-events="allowInterstitial"
                                            process-id="{{$task->processRequest->process->getKey()}}"
                                            instance-id="{{$task->processRequest->getKey()}}"
                                            token-id="{{$task->getKey()}}"
                                            :screen="{{json_encode($task->getScreen()->config)}}"
                                            :computed="{{json_encode($task->getScreen()->computed)}}"
                                            :custom-css="{{json_encode(strval($task->getScreen()->custom_css))}}"
                                            :data="{{$task->processRequest->data ? json_encode($task->processRequest->data) : '{}'}}">
                                        </task-screen>
                                      @endif
                                    @else
                                        <task-screen
                                            ref="taskScreen"
                                            :listen-process-events="allowInterstitial"
                                            process-id="{{$task->processRequest->process->getKey()}}"
                                            instance-id="{{$task->processRequest->getKey()}}"
                                            token-id="{{$task->getKey()}}"
                                            :screen="[{items:[]}]"
                                            :data="{{$task->processRequest->data ? json_encode($task->processRequest->data) : '{}'}}">
                                        </task-screen>
                                    @endif
                                </div>
                                @if ($task->getBpmnDefinition()->localName==='manualTask' || !$task->getScreen())
                                    <div class="card-footer">
                                        <button type="button" class="btn btn-primary" @click="submitTaskScreen">{{__('Complete Task')}}</button>
                                    </div>
                                @endif
                            @elseif ($task->advanceStatus==='completed')

                                <task-screen
                                    ref="taskWaitScreen"
                                    v-if="allowInterstitial"
                                    :listen-process-events="allowInterstitial"
                                    process-id="{{$task->processRequest->process->getKey()}}"
                                    instance-id="{{$task->processRequest->getKey()}}"
                                    token-id="{{$task->getKey()}}"
                                    :screen="{{json_encode($screenInterstitial->config)}}"
                                    :computed="{{json_encode($screenInterstitial->computed)}}"
                                    :custom-css="{{json_encode(strval($screenInterstitial->custom_css))}}"
                                    :data="{{$task->processRequest->data ? json_encode($task->processRequest->data) : '{}'}}"
                                    @activity-assigned="redirectToNextAssignedTask"
                                    @process-completed="redirectWhenProcessCompleted"
                                    @process-updated="refreshWhenProcessUpdated"
                                ></task-screen>
                                <div v-else class="card card-body text-center" v-cloak>
                                    <h1>{{ __('Task Completed') }} <i class="fas fa-clipboard-check"></i></h1>
                                </div>
                            @endif
                        </div>
                        @if ($task->processRequest->status === 'ACTIVE')
                            @can('editData', $task->processRequest)
                                <div id="tab-data" role="tabpanel" aria-labelledby="tab-data" class="card card-body border-top-0 tab-pane p-3">
                                    @include('tasks.editdata')
                                </div>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
            <div class="ml-md-3 mt-3 mt-md-0">
                <template v-if="dateDueAt">
                    <div class="card">
                        <div :class="statusCard">
                            <h4 style="margin:0; padding:0; line-height:1">{{__($task->advanceStatus)}}</h4>
                        </div>
                        <ul class="list-group list-group-flush w-100">
                            <li class="list-group-item" v-if="showDueAtDates">
                                <i class='far fa-calendar-alt'></i>
                                <small> {{__($dueLabels[$task->advanceStatus])}} @{{ moment(dateDueAt).fromNow() }}
                                </small>
                                <br>
                                @{{ moment(dateDueAt).format() }}
                            </li>


                            <li class="list-group-item" v-if="!showDueAtDates">
                                <i class='far fa-calendar-alt'></i>
                                <small> {{__($dueLabels[$task->advanceStatus])}} @{{ moment().to(moment(completedAt)) }}
                                </small>
                                <br>
                                @{{ moment(completedAt).format() }}
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
                                                <button type="button" class="btn btn-outline-secondary btn-block"
                                                        @click="show">
                                    <i class="fas fa-user-friends"></i> {{__('Reassign')}}
                                </button>
                                            @endif
                                <b-modal v-model="showReassignment" size="md" centered title="{{__('Reassign to')}}"
                                         @hide="cancelReassign"
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
                                             <template slot="noResult">
                                                {{ __('No elements found. Consider changing the search query.') }}
                                            </template>
                                            <template slot="noOptions">
                                                {{ __('No Data Available') }}
                                            </template>
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
                                <p v-if="!userRequested">{{__('Web Entry')}}</p>
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
        el: "#task",
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
          statusCard: "card-header text-capitalize text-white bg-success",
          userAssigned: [],
          userRequested: [],
          selectedUser: [],
          allowInterstitial: @json($allowInterstitial)
        },
        watch: {
          showReassignment (show) {
            show ? this.loadUsers() : null;
          }
        },
        computed: {
          dateDueAt () {
            return this.task.due_at;
          },
          createdAt () {
            return this.task.created_at;
          },
          completedAt () {
            return this.task.completed_at;
          },
          showDueAtDates () {
            return this.task.status !== "CLOSED";
          },
          disabled () {
            return this.selectedUser ? this.selectedUser.length === 0 : true;
          },
          styleDataMonaco () {
            let height = window.innerHeight * 0.55;
            return "height: " + height + "px; border:1px solid gray;";
          }
        },
        methods: {
          redirectWhenProcessCompleted() {
            window.location.href = `/requests/${this.task.process_request_id}`;
          },
          redirectWhenTaskCompleted() {
            if (!this.allowInterstitial) {
              document.location.href = "/tasks";
            } else {
              document.location.reload();
            }
          },
          refreshWhenProcessUpdated() {
            window.location.reload();
          },
          redirectToNextAssignedTask() {
            if (this.task.status == 'COMPLETED' || this.task.status == 'CLOSED') {
              window.ProcessMaker.apiClient.get(`/tasks?user_id=${this.assigned.id}&status=ACTIVE&process_request_id=${this.task.process_request_id}`).then((response) => {
                if (response.data.data.length > 0) {
                  const firstNextAssignedTask = response.data.data[0].id;
                  window.location.href = `/tasks/${firstNextAssignedTask}/edit`;
                } else if (this.task.process_request.status === 'COMPLETED') {
                  setTimeout(() => {
                    window.location.href = `/requests/${this.task.process_request_id}`;
                  }, 500);
                }
              });
            }
          },
          /**
           * Submit the task screen
           */
          submitTaskScreen () {
            this.$refs.taskScreen.submit();
          },
          // Data editor
          updateRequestData () {
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
          saveJsonData () {
            try {
              const value = JSON.parse(this.jsonData);
              this.updateRequestData();
            } catch (e) {
              // Invalid data
            }
          },
          editJsonData () {
            this.jsonData = JSON.stringify(this.data, null, 4);
          },
          // Reassign methods
          show () {
            this.showReassignment = true;
          },
          cancelReassign () {
            this.showReassignment = false;
            this.selectedUser = [];
          },
          reassignUser () {
            if (this.selectedUser) {
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
          loadUsers (filter) {
            filter = typeof filter === "string" ? "?filter=" + filter + "&" : "?";
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
          classHeaderCard (status) {
            let header = "bg-success";
            switch (status) {
              case "completed":
                header = "bg-secondary";
                break;
              case "overdue":
                header = "bg-danger";
                break;
            }
            return "card-header text-capitalize text-white " + header;
          },
          assignedUserAvatar (user) {
            return [{
              src: user.avatar,
              name: user.fullname
            }];
          },
          resizeMonaco () {
            let editor = this.$refs.monaco.getMonaco();
            editor.layout({height: window.innerHeight * 0.65});
          }
        },
        mounted () {
          this.statusCard = this.classHeaderCard(this.task.advanceStatus);
          this.userAssigned = this.assigned;
          this.userRequested = this.requested;
          this.updateRequestData = debounce(this.updateRequestData, 1000);
          this.editJsonData();
          if (this.allowInterstitial) {
            this.redirectToNextAssignedTask();
          }
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
