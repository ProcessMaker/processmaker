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
                        <li class="nav-item">
                            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab" aria-controls="pending" aria-selected="true">{{__('Pending Tasks')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="false">{{__('Request Summary')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab" aria-controls="completed" aria-selected="false">{{__('Completed')}}</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="requestTabContent">
                        <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                            <request-detail :process-request-id="requestId" status="ACTIVE"></request-detail>
                        </div>
                        <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            Summary
                        </div>
                        <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                            <request-detail :process-request-id="requestId" status="CLOSED"></request-detail>
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
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <h5>{{__('Requested By')}}</h5>
                            <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                          :input-data="requestBy"></avatar-image>
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Participants')}}</h5>
                            <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                          :input-data="participants"></avatar-image>
                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small>@{{ labelDate }}</small>
                            <br>
                            @{{ statusDate }}
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
                refreshTasks: 0
            };
        },
        computed: {
            /**
             * Get the list of participants in the request.
             *
             */
            participants(){
                const participants = [];
                this.request.participant_tokens.forEach(token => {
                    let user = token.user;
                    //change populate data
                    user.src = user.avatar;
                    user.title = user.fullname;
                    user.initials = user.firstname.match(/./u)[0] + user.lastname.match(/./u)[0];
                    participants.push(user);
                });
                return participants;
            },
            /**
             * Request Summary - that is blank place holder if there are in progress tasks,
             * if the request is completed it will show key value pairs.
             *
             */
            showSummary(){
                return this.request.status === 'COMPLETED';
            },
            /**
             * Get the summary of the Request.
             *
             */
            summary() {
                return [{key:"date", value:"5/67/8"}];
            },
            classStatusCard() {
                let header = {
                    "ACTIVE" : "bg-success",
                    "CLOSED" : "bg-secondary",
                    "ERROR" : "bg-danger"
                };
                return 'card-header text-capitalize text-white ' + header[this.request.status.toUpperCase()];
            },
            statusLabel() {
                let label = {
                    "ACTIVE" : 'In Progress',
                    "CLOSED" : 'Completed',
                    "ERROR" : 'Error'
                };
                return label[this.request.status.toUpperCase()];
            },
            labelDate() {
                let label = {
                    "ACTIVE" : 'Create On',
                    "CLOSED" : 'Completed On',
                    "ERROR" : 'Failed On'
                };
                return label[this.request.status.toUpperCase()];
            },
            statusDate() {
                let status = {
                    "ACTIVE" : this.request.created_at,
                    "CLOSED" : this.request.completed_at,
                    "ERROR" :  this.request.updated_at
                };
                return status[this.request.status.toUpperCase()];
            },
            requestBy() {
                return [{
                    src: this.request.user.avatar,
                    name: this.request.user.fullname
                }]
            },
        },
        methods: {
            /**
             * Refresh the Request details.
             *
             */
            refreshRequest() {
                ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
                    params: {
                        include: 'participantTokens,user'
                    }
                })
                .then((response) => {
                    for(let attribute in response) {
                        this.updateModel(this.request, attribute, response[attribute]);
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
                        if (token.request_id===this.requestId) {
                            this.refreshRequest();
                        }
                    });
            }
        },
        mounted() {
            this.listenRequestUpdates();
        },
    });
</script>
@endsection
