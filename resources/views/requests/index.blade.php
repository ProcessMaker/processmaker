@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
@include('shared.breadcrumbs', ['routes' => [
    __('Requests') => route('requests.index'),
    function() use ($title) { return [__($title), null]; }
]])
@endsection
@section('content')
<div class="px-3 page-content mb-0" id="requests-listing">
    <div class="row">
        <div class="col-sm-12">
            <counter-card-group>
                <counter-card
                    color="info"
                    icon="id-badge"
                    link="{{ route('requests_by_type', ['type' => '']) }}"
                    title="My Requests"
                    url='requests?total=true&pmql=(status = "In Progress") AND (requester = "{{ Auth::user()->username }}")'
                ></counter-card>
                <counter-card
                    color="success"
                    icon="clipboard-list"
                    link="{{ route('requests_by_type', ['type' => 'in_progress']) }}"
                    title="In Progress"
                    url='requests?total=true&pmql=(status = "In Progress")'
                ></counter-card>
                <counter-card
                    color="primary"
                    icon="clipboard-check"
                    link="{{ route('requests_by_type', ['type' => 'completed']) }}"
                    title="Completed"
                    url='requests?total=true&pmql=(status = "Completed")'
                ></counter-card>
                @can('view-all_requests')
                    <counter-card
                        color="warning"
                        icon="clipboard"
                        link="{{ route('requests_by_type', ['type' => 'all']) }}"
                        title="All Requests"
                        url='requests?total=true'
                    ></counter-card>
                @endcan
            </counter-card-group>
            <advanced-search ref="advancedSearch" type="requests" :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}" :param-status="status" :param-requester="requester" @change="onChange" @submit="onSearch"></advanced-search>
            <requests-listing ref="requestList" :filter="filter" :pmql="pmql"></requests-listing>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    //Data needed for default search
    window.Processmaker.user = @json($currentUser);
    window.Processmaker.status = '{{ $type }}';
</script>
<script src="{{mix('js/requests/index.js')}}"></script>
@endsection

@section('css')
<style>
    .has-search .form-control {
        padding-left: 2.375rem;
    }

    .has-search .form-control-feedback {
        position: absolute;
        z-index: 2;
        display: block;
        width: 2.375rem;
        height: 2.375rem;
        line-height: 2.375rem;
        text-align: center;
        pointer-events: none;
        color: #aaa;
    }

    .card-border {
        border-radius: 4px !important;
    }

    .card-size-header {
        max-width: 90px;
    }
    .option__image {
        width:27px;
        height: 27px;
        border-radius: 50%;
    }
    .initials {
        display:inline-block;
        text-align: center;
        font-size: 12px;
        max-width:25px;
        max-height: 25px;
        min-width:25px;
        min-height: 25px;
        border-radius: 50%;
    }
</style>
@endsection
