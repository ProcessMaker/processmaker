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
            <template>
                <div class="card-deck-flex">

                    <b-card header-class="card-size-header px-4 px-xl-5 d-flex d-md-none d-lg-flex align-items-center justify-content-center border-0"
                        text-variant="white" class="bg-info d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-id-badge fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => '']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$startedMe}}</h1>
                            <h6 class="card-text">{{__('My Requests')}}</h6>
                        </a>
                    </b-card>

                    <b-card header-class="card-size-header px-4 px-xl-5 d-flex d-md-none d-lg-flex align-items-center justify-content-center border-0"
                        text-variant="white" class="bg-success d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-clipboard-list fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'in_progress']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$inProgress}}</h1>
                            <h6 class="card-text">{{__('In Progress')}}</h6>
                        </a>
                    </b-card>

                    <b-card header-class="card-size-header px-4 px-xl-5 d-flex d-md-none d-lg-flex align-items-center justify-content-center border-0"
                        text-variant="white" class="bg-primary d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-clipboard-check fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'completed']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$completed}}</h1>
                            <h6 class="card-text">{{__('Completed')}}</h6>
                        </a>
                    </b-card>
                    @can('view-all_requests')
                    <b-card header-class="card-size-header px-4 px-xl-5 d-flex d-md-none d-lg-flex align-items-center justify-content-center border-0"
                        text-variant="white" class="bg-warning d-flex flex-row  card-border border-0">
                        <i slot="header" class='fas fa-clipboard fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'all']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$allRequest}}</h1>
                            <h6 class="card-text">{{__('All Requests')}}</h6>
                        </a>
                    </b-card>
                    @endcan

                </div>
                <advanced-search ref="advancedSearch" type="requests" :param-status="status" :param-requester="requester" @change="onChange" @submit="onSearch"></advanced-search>
            </template>
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
