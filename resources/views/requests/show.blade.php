@extends('layouts.layout')

@section('title')
{{__('Task Detail')}}
@endsection

@section('content')
<div id="request" class="container d-flex">
    <div class="list-group">
        <div class="list-group-item list-group-item-action bg-success text-light"><h3>{{__('In Progress')}}</h3></div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
                                                                                                        style="border-radius: 50%;">
            Jane Manager</div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
        <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
            <br /> <h4>10/12/18 18:25</h4></div>
    </div>
    <div class="list-group">
        <div class="list-group-item list-group-item-action bg-secondary text-light"><h3>{{__('Completed')}}</h3></div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
                                                                                                        style="border-radius: 50%;">
            Jane Manager</div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
        <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
            <br /> <h4>10/12/18 18:25</h4></div>
    </div>
    <div class="list-group">
        <div class="list-group-item list-group-item-action bg-danger text-light"><h3>{{__('Error')}}</h></div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Requested By')}}</h4> <br /> <img src="https://via.placeholder.com/40"
                                                                                                        style="border-radius: 50%;">
            Jane Manager</div>
        <div class="list-group-item list-group-item-action"><h4>{{__('Participants')}}</h4> <br />
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;">
            <img src="https://via.placeholder.com/40" style="border-radius: 50%;"></div>
        <div class="list-group-item list-group-item-action"><i class="far fa-calendar-alt fa-lg"></i> {{__('Completed
      On')}}
            <br /> <h4>10/12/18 18:25</h4></div>
    </div>

    {{-- <request-status process-id="{{$instance->process->id}}" instance-id="{{$instance->id}}"></request-status> --}}
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('js')
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
            participants(){
                const participants = [];
                this.participantTokens;
                return participants;
            },
            /**
             * Request Summary - that is blank place holder if there are in progress tasks,
             * if the request is completed it will show key value pairs.
             */
            showSummary(){
                return this.request.status !== 'ACTIVE';
            },
            summary() {
                return ;
            }
        },
        methods: {
            refreshData() {
                ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
                    params: {
                        include: 'participantTokens'
                    }
                })
                .then((response) => {
                    for(let attribute in response) {
                        this.updateModel(this.request, attribute, response[attribute]);
                    }
                });
            },
            updateModel(obj, prop, value, defaultValue) {
                const descriptor = Object.getOwnPropertyDescriptor(obj, prop);
                value = value !== undefined ? value : (descriptor ? obj[prop] : defaultValue);
                if (descriptor && !(descriptor.get instanceof Function)) {
                    delete obj[prop];
                    Vue.set(obj, prop, value);
                } else if (descriptor && obj[prop] !== value) {
                    Vue.set(obj, prop, value);
                }
            }
        }
    });
</script>
@endsection