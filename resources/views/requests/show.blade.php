@extends('layouts.layout')

@section('title')
    {{__('Request Detail')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('meta')
    <meta name="request-id" content="{{ $request->id }}">
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Requests') => route('requests.index'),
        $request->name . ' #'. $request->getKey() => null,
    ]])
@endsection
@section('content')
    <div id="request" class="container">
        <div class="row">
            <div class="col-md-8">

                <div class="container-fluid">
                    <ul class="nav nav-tabs" id="requestTab" role="tablist">
                        <template v-if="status">
                            @if($request->status === 'ERROR')
                                <li class="nav-item">
                                    <a class="nav-link active" id="errors-tab" data-toggle="tab" href="#errors"
                                       role="tab"
                                       aria-controls="errors" aria-selected="false">{{__('Errors')}}</a>
                                </li>
                            @endif
                            <li class="nav-item" v-if="!showSummary">
                                <a class="nav-link" :class="{ active: activePending }" id="pending-tab"
                                   data-toggle="tab" href="#pending" role="tab"
                                   aria-controls="pending" aria-selected="true">{{__('Tasks')}}</a>
                            </li>
                            <li class="nav-item">
                                <a id="summary-tab" data-toggle="tab" href="#summary" role="tab"
                                   aria-controls="summary" aria-selected="false"
                                   v-bind:class="{ 'nav-link':true, active: showSummary }">
                                    {{__('Summary')}}
                                </a>
                            </li>
                            @if ($request->status === 'COMPLETED' && !$request->errors)
                                @can('editData', $request)
                                    <li>
                                        <a id="editdata-tab" data-toggle="tab" href="#editdata" role="tab"
                                           aria-controls="editdata" aria-selected="false"
                                           class="nav-link">
                                            {{__('Data')}}
                                        </a>
                                    </li>
                                @endcan
                            @endif
                            <li class="nav-item">
                                <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab"
                                   aria-controls="completed" aria-selected="false">{{__('Completed')}}</a>
                            </li>
                            @if(count($files) > 0 )
                                <li class="nav-item">
                                    <a class="nav-link" id="files-tab" data-toggle="tab" href="#files" role="tab"
                                       aria-controls="files" aria-selected="false">{{__('Files')}}</a>
                                </li>
                            @endif
                                <li class="nav-item" v-show="canViewPrint">
                                    <a class="nav-link" id="forms-tab" data-toggle="tab" href="#forms"
                                       role="tab" aria-controls="forms" aria-selected="false">
                                        {{__('Forms')}}
                                    </a>
                                </li>
                        </template>
                    </ul>
                    <div class="tab-content" id="requestTabContent">






                            <div class="tab-pane card card-body border-top-0" :class="{ active: activeErrors }" id="errors" role="tabpanel"
                                 aria-labelledby="errors-tab">
                                <request-errors :errors="errorLogs"></request-errors>
                            </div>
                            <div class="tab-pane fade show card card-body border-top-0 p-3" :class="{ active: activePending }" id="pending" role="tabpanel"
                                 aria-labelledby="pending-tab" v-if="!showSummary">
                                <request-detail ref="pending" :process-request-id="requestId" status="ACTIVE">
                                </request-detail>
                            </div>
                            <div class="card card-body border-top-0 p-3" v-bind:class="{ 'tab-pane':true, active: showSummary }" id="summary"
                                 role="tabpanel" aria-labelledby="summary-tab">
                                <template v-if="showSummary">
                                    <template v-if="showScreenSummary">
                                        <div class="card">
                                            <div class="card-body">
                                                <task-screen ref="screen" :screen="screenSummary" :data="dataSummary"/>
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <template v-if="summary.length > 0">
                                            <div class="card">
                                                <table class="vuetable table table-hover">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">{{ __('Key') }}</th>
                                                        <th scope="col">{{ __('Value') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="item in summary">
                                                        <td>@{{item.key}}</td>
                                                        <td>@{{item.value}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5>
                                                        {{ __('No Data Found') }}
                                                    </h5>
                                                </div>

                                                <div class="card-body">
                                                    <p class="card-text">
                                                        {{ __("Sorry, this request doesn't contain any information.") }}
                                                    </p>
                                                </div>
                                            </div>
                                        </template>

                                    </template>
                                </template>
                                <template v-else>
                                    <template v-if="showScreenRequestDetail">
                                        <div class="card">
                                            <div class="card-body">
                                                <task-screen ref="screenRequestDetail" :screen="screenRequestDetail" :data="dataSummary"/>
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="card">
                                            <div class="card-header">
                                                <h5>
                                                    {{ __('Request In Progress') }}
                                                </h5>
                                            </div>

                                            <div class="card-body">
                                                <p class="card-text">
                                                    {{__('This Request is currently in progress.')}}
                                                    {{__('This screen will be populated once the Request is completed.')}}
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </div>
                            @if ($request->status === 'COMPLETED')
                                @can('editData', $request)
                                    <div id="editdata" role="tabpanel" aria-labelledby="editdata" class="tab-pane card card-body border-top-0 p-3">
                                        @include('tasks.editdata')
                                    </div>
                                @endcan
                            @endif
                            <div class="tab-pane fade card card-body border-top-0 p-3" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                                <request-detail ref="completed" :process-request-id="requestId" status="CLOSED">
                                </request-detail>
                            </div>
                            <div class="tab-pane fade card card-body border-top-0 p-3" id="files" role="tabpanel" aria-labelledby="files-tab">
                                <div class="card">
                                    <div>
                                        <table class="vuetable table table-hover">
                                            <thead>
                                            <tr>
                                                <th>{{__('File Name')}}</th>
                                                <th>{{__('MIME Type')}}</th>
                                                <th>{{__('Created At')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($files as $file)
                                                <tr>
                                                    <td>
                                                        <a href="{{url('request/' .$request->id .'/files/' . $file->id)}}">{{$file->file_name}}</a>
                                                    </td>
                                                    <td>{{$file->mime_type}}</td>
                                                    <td>{{ $file->created_at->format('m/d/y h:i a')}}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade card card-body border-top-0 p-3" id="forms" role="tabpanel" aria-labelledby="forms-tab" v-show="canViewPrint">
                                <request-screens :id="requestId" :information="dataSummary" :screens="screenRequested" ref="forms">
                                </request-screens>
                            </div>
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
                @if($canViewComments === true)
                    <div>
                        <comments commentable_id="{{ $request->getKey() }}"
                                  commentable_type="{{ get_class($request) }}"/>
                    </div>
                @endif

            </div>
            <div class="col-md-4">
                <template v-if="statusLabel">
                    <div class="card">
                        <div :class="classStatusCard">
                            <h4 style="margin:0; padding:0; line-height:1">@{{ __(statusLabel) }}</h4>
                        </div>
                        <ul class="list-group list-group-flush w-100">
                            <li class="list-group-item">
                                <h5>{{__('Requested By')}}</h5>
                                <avatar-image v-if="userRequested" size="32"
                                              class="d-inline-flex pull-left align-items-center"
                                              :input-data="requestBy" display-name="true"></avatar-image>
                                <span v-if="!userRequested">{{__('Web Entry')}}</span>
                            </li>

                            @if($canCancel == true && $request->status === 'ACTIVE')
                                <template>
                                    <li class="list-group-item">
                                        <h5>{{__('Cancel Request')}}</h5>
                                        <button type="button" class="btn btn-outline-danger btn-block"
                                                data-toggle="modal" data-target="#cancelModal">
                                            <i class="fas fa-stop-circle"></i> {{__('Cancel')}}
                                        </button>
                                    </li>
                                </template>
                            @endif
                            @if($canManuallyComplete == true)
                                <li class="list-group-item">
                                    <h5>{{__('Manually Complete Request')}}</h5>
                                    <button type="button" class="btn btn-outline-success btn-block"
                                            data-toggle="modal" @click="completeRequest">
                                        <i class="fas fa-stop-circle"></i> {{__('Complete')}}
                                    </button>
                                </li>
                            @endif
                            @if($request->parentRequest)
                            <li class="list-group-item">
                              <h5>{{__('Parent Request')}}</h5>
                              <i :class="requestStatusClass('{{$request->parentRequest->status}}')"></i>
                            <a href="/requests/{{$request->parentRequest->getKey()}}">{{$request->parentRequest->name}}</a>
                            </li>
                            @endif
                            @if(count($request->childRequests))
                            <li class="list-group-item">
                              <h5>{{__('Child Requests')}}</h5>
                              @foreach($request->childRequests as $childRequest)
                              <div>
                              <i :class="requestStatusClass('{{$childRequest->status}}')"></i>
                              <a href="/requests/{{$childRequest->getKey()}}">{{$childRequest->name}}</a>
                              </div>
                              @endforeach
                            </li>
                            @endif
                            <li class="list-group-item">
                                <h5>{{__('Participants')}}</h5>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                              :input-data="participants" hide-name="true"></avatar-image>
                            </li>
                            <li class="list-group-item">
                                <h5>@{{statusLabel}}</h5>
                                <i class="far fa-calendar-alt"></i>
                                <small>@{{ moment(statusDate).format() }}</small>
                                <br>

                            </li>
                        </ul>
                    </div>
                </template>
            </div>
            <div class="modal" tabindex="-1" role="dialog" id="cancelModal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{__('Caution!')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p data-v-27f69fb6="" class=""><span
                                        data-v-27f69fb6=""><b>{{__('Are you sure you want cancel this request ?')}}</b></span>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                    data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-secondary" @click="okCancel" :disabled="disabled">
                                {{__('Confirm')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')

    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach

    <script src="{{mix('js/requests/show.js')}}"></script>
    <script>
      new Vue({
        el: "#request",
        data() {
          return {
            showCancelRequest: false,
            //Edit data
            fieldsToUpdate: [],
            jsonData: "",
            selectedData: '',
            monacoLargeOptions: {
              automaticLayout: true,
            },
            showJSONEditor: false,
            data: @json($request->data),
            requestId: @json($request->getKey()),
            screenRequested: @json($screenRequested),
            request: @json($request),
            files: @json($files),
            refreshTasks: 0,
            canCancel: @json($canCancel),
            canViewPrint : @json($canPrintScreens),
            status: 'ACTIVE',
            userRequested: [],
            errorLogs: @json(['data'=>$request->errors]),
            disabled: false
          };
        },
        computed: {
          activeErrors() {
            return this.request.status === 'ERROR';
          },
          activePending() {
            return this.request.status === 'ACTIVE';
          },
          /**
           * Get the list of participants in the request.
           *
           */
          participants() {
            return this.request.participants;
          },
          /**
           * Request Summary - that is blank place holder if there are in progress tasks,
           * if the request is completed it will show key value pairs.
           *
           */
          showSummary() {
            return this.request.status === 'COMPLETED' || this.request.status === 'CANCELED';
          },
          /**
           * If the screen summary is configured.
           **/
          showScreenSummary() {
            return this.request.summary_screen !== null
          },
          /**
           * Get the summary of the Request.
           *
           */
          summary() {
            return this.request.summary;
          },
          /**
           * Get Screen summary
           * */
          screenSummary() {
            return this.request.summary_screen.config;
          },
          /**
           * prepare data screen
           **/
          dataSummary() {
            let options = {};
            this.request.summary.forEach(option => {
              options[option.key] = option.value
            });
            return options;
          },
          /**
           * If the screen request detail is configured.
           **/
          showScreenRequestDetail() {
            return !!this.request.request_detail_screen;
          },
          /**
           * Get Screen request detail
           * */
          screenRequestDetail() {
            return this.request.request_detail_screen ? this.request.request_detail_screen.config : null;
          },
          classStatusCard() {
            let header = {
              "ACTIVE": "bg-success",
              "COMPLETED": "bg-secondary",
              "CANCELED": 'bg-danger',
              "ERROR": "bg-danger"
            };
            return 'card-header text-capitalize text-white ' + header[this.request.status.toUpperCase()];
          },
          labelDate() {
            let label = {
              "ACTIVE": 'Created',
              "COMPLETED": 'Completed On',
              "CANCELED": 'Canceled ',
              "ERROR": 'Failed On'
            };
            return label[this.request.status.toUpperCase()];
          },
          statusDate() {
            let status = {
              "ACTIVE": this.request.created_at,
              "COMPLETED": this.request.completed_at,
              "CANCELED": this.request.updated_at,
              "ERROR": this.request.updated_at
            };

            return status[this.request.status.toUpperCase()];
          },
          statusLabel() {
            let status = {
              "ACTIVE": "{{__('In Progress')}}",
              "COMPLETED": "{{__('Completed')}}",
              "CANCELED": "{{__('Canceled')}}",
              "ERROR": "{{__('Error')}}",
            };

            return status[this.request.status.toUpperCase()];
          },
          requestBy() {
            return [this.request.user]
          },
        },
        methods: {
          requestStatusClass(status) {
            status = status.toLowerCase();
            let bubbleColor = {
              active: "text-success",
              inactive: "text-danger",
              error: "text-danger",
              draft: "text-warning",
              archived: "text-info",
              completed: "text-primary",
            };
            return 'fas fa-circle ' + bubbleColor[status] + ' small';
          },
          // Data editor
          updateRequestData() {
            const data = JSON.parse(this.jsonData);
            ProcessMaker.apiClient
              .put("requests/" + this.requestId, {
                data: data
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
          /**
           * Refresh the Request details.
           *
           */
          refreshRequest() {
            this.$refs.pending.fetch();
            this.$refs.completed.fetch();
            ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
              params: {
                include: 'participants,user,summary,summaryScreen'
              }
            })
              .then((response) => {
                for (let attribute in response.data) {
                  this.updateModel(this.request, attribute, response.data[attribute]);
                }
                this.refreshTasks++;
              });
          },
          /**
           * Update a model property.
           *
           */
          updateModel(obj, prop, value, defaultValue) {
            const descriptor = Object.getOwnPropertyDescriptor(obj, prop);
            value = value !== undefined ? value : (descriptor ? obj[prop] : defaultValue);
            if (descriptor && !(descriptor.get instanceof Function)) {
              delete obj[prop];
              Vue.set(obj, prop, value);
            } else if (descriptor && obj[prop] !== value) {
              Vue.set(obj, prop, value);
            }
          },
          /**
           * Listen for Request updates.
           *
           */
          listenRequestUpdates() {
            let userId = document.head.querySelector('meta[name="user-id"]').content;
            Echo.private(`ProcessMaker.Models.User.${userId}`)
              .notification((token) => {
                if (token.request_id === this.requestId) {
                  this.refreshRequest();
                }
              });
          },
          /**
           * disable buttons in screen
           */
          cleanScreenButtons() {
            if (this.showScreenSummary) {
              this.$refs.screen.screen[0].items.forEach(item => {
                item.config.disabled = true;
                if (item.component === 'FormButton') {
                  item.config.event = '';
                  item.config.variant = item.config.variant + '  disabled';
                }
              });
            }
          },
          okCancel() {
            //single click
            if (this.disabled) {
              return
            }
            this.disabled = true;
            ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
              status: 'CANCELED'
            })
              .then(response => {
                ProcessMaker.alert(this.$t('The request was canceled.'), 'success');
                window.location.reload();
              })
              .catch(error => {
                this.disabled = false;
              });
          },
          cancelRequest() {
            this.showCancelRequest = true;
          },
          completeRequest() {
            ProcessMaker.confirmModal(
              this.$t("Caution!"),
              this.$t("Are you sure you want to complete this request?"),
              "",
              () => {
                ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
                  status: 'COMPLETED'
                }).then(() => {
                  ProcessMaker.alert(this.$t('Request Completed'), 'success')
                  location.reload()
                })
              })
          }
        },
        mounted() {
          this.listenRequestUpdates();
          this.cleanScreenButtons();
          this.editJsonData();
        },
      });
    </script>
@endsection
