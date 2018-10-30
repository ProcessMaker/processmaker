@extends('layouts.layout')

@section('title')
    {{__('Request Detail')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('content')
    <div id="request" class="container">
        <h1>Request Title #123</h1>
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

                    {{--<ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">{{__('Pending Tasks')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">{{__('Request Summary')}}</a>
                        </li>
                        <li class="nav-item justify-content-end">
                            <a class="nav-link" href="#">{{__('Completed')}}</a>
                        </li>
                    </ul>

                    <div class="data-table">
                        <table data-v-15965e3b="" class="vuetable table table-hover">
                            <thead data-v-15965e3b="">
                            <tr data-v-15965e3b="">
                                <th data-v-15965e3b="" class="vuetable-th-slot-name sortable">TASK <i
                                            class="sort-icon fas fa-sort"></i></th>
                                <th data-v-15965e3b="" id="_previousUser" class="vuetable-th-previousUser sortable">
                                    ASSIGNED <i class="sort-icon fas fa-sort"></i></th>
                                <th data-v-15965e3b="" id="_due_at" class="vuetable-th-due_at ascending sortable">DUE
                                    DATE <i class="sort-icon fas fa-sort-up"></i></th>
                                <th data-v-15965e3b="" class="vuetable-th-slot-actions"></th>
                            </tr>
                            </thead>
                            <tbody data-v-15965e3b="" class="vuetable-body">
                            <tr data-v-15965e3b="" item-index="0" render="true" class="">
                                <td data-v-15965e3b="" class="vuetable-slot"><a data-v-15965e3b="" href="#"
                                                                                target="_self" class="">
                                        Get available days
                                    </a></td>
                                <td data-v-15965e3b="" class=""><img class="avatar-image-list avatar-circle-list"
                                                                     src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                                    <span>admin admin</span></td>
                                <td data-v-15965e3b="" class=""><span class="text-primary">2018-10-27 16:26:23</span>
                                </td>
                                <td data-v-15965e3b="" class="vuetable-slot">
                                    <div data-v-15965e3b="" class="actions">
                                        <div data-v-15965e3b="" class="popout">
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i data-v-15965e3b=""
                                                                              class="fas fa-edit"></i></button>
                                            <button data-v-15965e3b="" title=""
                                                    type="button" class="btn btn-action" data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-pause"></i></button>
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-undo"></i></button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-v-15965e3b="" item-index="1" render="true" class="">
                                <td data-v-15965e3b="" class="vuetable-slot"><a data-v-15965e3b="" href="#"
                                                                                target="_self" class="">
                                        Fill a request
                                    </a></td>
                                <td data-v-15965e3b="" class=""><img class="avatar-image-list avatar-circle-list"
                                                                     src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                                    <span>admin admin</span></td>
                                <td data-v-15965e3b="" class=""><span class="text-primary">2018-10-27 16:26:26</span>
                                </td>
                                <td data-v-15965e3b="" class="vuetable-slot">
                                    <div data-v-15965e3b="" class="actions">
                                        <div data-v-15965e3b="" class="popout">
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i data-v-15965e3b=""
                                                                              class="fas fa-edit"></i></button>
                                            <button data-v-15965e3b="" title=""
                                                    type="button" class="btn btn-action" data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-pause"></i></button>
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-undo"></i></button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-v-15965e3b="" item-index="1" render="true" class="">
                                <td data-v-15965e3b="" class="vuetable-slot"><a data-v-15965e3b="" href="#"
                                                                                target="_self" class="">
                                        Fill a request
                                    </a></td>
                                <td data-v-15965e3b="" class=""><img class="avatar-image-list avatar-circle-list"
                                                                     src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                                    <span>admin admin</span></td>
                                <td data-v-15965e3b="" class=""><span class="text-primary">2018-10-27 16:26:26</span>
                                </td>
                                <td data-v-15965e3b="" class="vuetable-slot">
                                    <div data-v-15965e3b="" class="actions">
                                        <div data-v-15965e3b="" class="popout">
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i data-v-15965e3b=""
                                                                              class="fas fa-edit"></i></button>
                                            <button data-v-15965e3b="" title=""
                                                    type="button" class="btn btn-action" data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-pause"></i></button>
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-undo"></i></button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-v-15965e3b="" item-index="1" render="true" class="">
                                <td data-v-15965e3b="" class="vuetable-slot"><a data-v-15965e3b="" href="#"
                                                                                target="_self" class="">
                                        Fill a request
                                    </a></td>
                                <td data-v-15965e3b="" class=""><img class="avatar-image-list avatar-circle-list"
                                                                     src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                                    <span>admin admin</span></td>
                                <td data-v-15965e3b="" class=""><span class="text-primary">2018-10-27 16:26:26</span>
                                </td>
                                <td data-v-15965e3b="" class="vuetable-slot">
                                    <div data-v-15965e3b="" class="actions">
                                        <div data-v-15965e3b="" class="popout">
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i data-v-15965e3b=""
                                                                              class="fas fa-edit"></i></button>
                                            <button data-v-15965e3b="" title=""
                                                    type="button" class="btn btn-action" data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-pause"></i></button>
                                            <button data-v-15965e3b="" title="" type="button" class="btn btn-action"
                                                    data-original-title=""><i
                                                        data-v-15965e3b="" class="fas fa-undo"></i></button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>--}}
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 style="margin:0; padding:0; line-height:1">Error</h4>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <h5>Requested By</h5>
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif"> <span>Joe Manager</span>
                        </li>
                        <li class="list-group-item">
                            <h5>Participants</h5>
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                            <img style="width:32px; height:32px" class="rounded-circle"
                                 src="https://bpm4.processmaker.local/storage/1/avatar-placeholder.gif">
                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small>Failed On</small>
                            <br>
                            10/12/2017 17:30
                        </li>

                    </ul>
                </div>
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
                    participants.push(token.user);
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
            }
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
        }
    });
</script>
@endsection
