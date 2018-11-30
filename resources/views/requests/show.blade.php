@extends('layouts.layout')

@section('title')
    {{__('Request Detail')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('content')
    <div id="request" class="container">
        <h1>{{$request->name}} # {{$request->getKey()}}</h1>
        <div class="row">
            <div class="col-8">

                <div class="container-fluid">
                    <ul class="nav nav-tabs" id="requestTab" role="tablist">
                        <template v-if="status">
                            <li class="nav-item" v-if="!showSummary">
                                <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab"
                                   aria-controls="pending" aria-selected="true">{{__('Pending Tasks')}}</a>
                            </li>
                            <li class="nav-item">
                                <a id="summary-tab" data-toggle="tab" href="#summary" role="tab"
                                   aria-controls="summary" aria-selected="false"
                                   v-bind:class="{ 'nav-link':true, active: showSummary }">
                                    {{__('Request Summary')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab"
                                   aria-controls="completed" aria-selected="false">{{__('Completed')}}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="files-tab" data-toggle="tab" href="#files" role="tab"
                                   aria-controls="files" aria-selected="false">{{__('Attached Files')}}</a>
                            </li>
                        </template>
                    </ul>
                    <div class="tab-content" id="requestTabContent">
                        <div class="tab-pane fade show active" id="pending" role="tabpanel"
                             aria-labelledby="pending-tab" v-if="!showSummary">
                            <request-detail ref="pending" :process-request-id="requestId" status="ACTIVE">
                            </request-detail>
                        </div>
                        <div v-bind:class="{ 'tab-pane':true, active: showSummary }" id="summary"
                             role="tabpanel" aria-labelledby="summary-tab">
                            <template v-if="showSummary">
                                <template v-if="showScreenSummary">
                                    <div class="card m-3">
                                        <div class="card-body">
                                            <task-screen ref="screen" :screen="screenSummary" :data="dataSummary"/>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
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
                                </template>
                            </template>
                            <template v-else>
                                <div class="card m-3">
                                    <div class="card-header">
                                        <h5>
                                            {{ __('Request In Progress') }}
                                        </h5>
                                    </div>

                                    <div class="card-body">
                                        <p class="card-text">
                                            {{__('This request is currently in progress.')}}
                                            {{__('This screen will be populated once the request is completed.')}}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                            <request-detail ref="completed" :process-request-id="requestId" status="CLOSED">
                            </request-detail>
                        </div>
                        <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                        <div class="mt-3">
                            <div>
                                <table class="vuetable table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{__('File Name')}}</th>
                                            <th>{{__('Mime Type')}}</th>
                                            <th>{{__('Created At')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($files as $file)
                                        <tr>
                                            <td><a href="{{url('request/' .$request->id .'/files/' . $file->id)}}">{{$file->file_name}}</a></td>
                                            <td>{{$file->mime_type}}</td>
                                            <td>{{ $file->created_at->format('m/d/y h:i a')}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <template v-if="statusLabel">
                    <div class="card">
                        <div :class="classStatusCard">
                            <h4 style="margin:0; padding:0; line-height:1">@{{ statusLabel }}</h4>
                        </div>
                        <ul class="list-group list-group-flush w-100">
                            <li class="list-group-item">
                                <h5>{{__('Requested By')}}</h5>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                              :input-data="requestBy" display-name="true"></avatar-image>
                            </li>
                            <template v-if="statusLabel == 'In Progress'">
                            <li class="list-group-item">
                                <h5>{{__('Cancel Request')}}</h5>
                                <button type="button" class="btn btn-outline-danger btn-block"
                                        @click="cancelRequest">
                                    <i class="fas fa-stop-circle"></i> {{__('Cancel')}}
                                </button>
                            </li>
                            </template>

                            <li class="list-group-item">
                                <h5>{{__('Participants')}}</h5>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                              :input-data="participants" hide-name="true"></avatar-image>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-calendar-alt"></i>
                                <small>@{{ labelDate }} @{{ moment(statusDate).fromNow() }}</small>
                                <br>
                                @{{ moment(statusDate).format() }}
                            </li>
                        </ul>
                    </div>
                </template>
            </div>

        </div>
    </div>

@endsection

@section('js')
    <script src="{{mix('js/requests/show.js')}}"></script>
    <script>
        new Vue({
            el: "#request",
            data() {
                return {
                    requestId: @json($request->getKey()),
                    request: @json($request),
                    files: @json($files),
                    refreshTasks: 0,
                    status: 'ACTIVE'
                };
            },
            computed: {
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
                    return this.request.process.summary_screen !== null
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
                    return this.request.process.summary_screen.config;
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
                classStatusCard() {
                    let header = {
                        "ACTIVE": "bg-success",
                        "COMPLETED": "bg-secondary",
                        "CANCELED": 'bg-danger',
                        "ERROR": "bg-danger"
                    };
                    return 'card-header text-capitalize text-white ' + header[this.request.status.toUpperCase()];
                },
                statusLabel() {
                    let label = {
                        "ACTIVE": 'In Progress',
                        "COMPLETED": 'Completed',
                        "CANCELED": 'Canceled',
                        "ERROR": 'Error'
                    };

                    if (this.request.status.toUpperCase() === 'COMPLETED') {
                        this.status = 'Completed'
                    }
                    if (this.request.status.toUpperCase() === 'CANCELED') {
                        this.status = 'Canceled'
                    }

                    return label[this.request.status.toUpperCase()];
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
                requestBy() {
                    return [this.request.user]
                },
            },
            methods: {
                /**
                 * Refresh the Request details.
                 *
                 */
                refreshRequest() {
                    this.$refs.pending.fetch();
                    this.$refs.completed.fetch();
                    ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
                        params: {
                            include: 'participants,user,summary,process.summaryScreen'
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
                cancelRequest() {
                    ProcessMaker.confirmModal(
                        "Caution!",
                        "<b>Are you sure you want cancel this request ?</b>",
                        "",
                        () => {
                            ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
                                status: 'CANCELED'
                            })
                                .then(response => {
                                    ProcessMaker.alert('Request Canceled Successfully', 'success');
                                    window.location.reload();
                                });
                        }
                    );
                }
            },
            mounted() {
                this.listenRequestUpdates();
                this.cleanScreenButtons();
            },
        });
    </script>
@endsection
