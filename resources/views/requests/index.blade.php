@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('content')
@include('shared.breadcrumbs', ['routes' => [
    __('Requests') => route('requests.index'),
    function() use ($title) { return [__($title), null]; }
]])
<div class="container page-content mt-2" id="requests-listing">
    <div class="row">
        <div class="col-sm-12">
            <template v-if="title">
                <b-card-group deck>

                    <b-card header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-info mb-3 d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-id-badge fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => '']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$startedMe}}</h1>
                            <h6 class="card-text">{{__('My Requests')}}</h6>
                        </a>
                    </b-card>

                    <b-card header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-success mb-3 d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-clipboard-list fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'in_progress']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$inProgress}}</h1>
                            <h6 class="card-text">{{__('In Progress')}}</h6>
                        </a>
                    </b-card>

                    <b-card header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-primary mb-3 d-flex flex-row card-border border-0">
                        <i slot="header" class='fas fa-clipboard-check fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'completed']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$completed}}</h1>
                            <h6 class="card-text">{{__('Completed')}}</h6>
                        </a>
                    </b-card>
                    @if (Auth::user()->is_administrator)
                    <b-card header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-warning mb-3 d-flex flex-row  card-border border-0">
                        <i slot="header" class='fas fa-clipboard fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'all']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$allRequest}}</h1>
                            <h6 class="card-text">{{__('All Requests')}}</h6>
                        </a>
                    </b-card>
                    @endif

                </b-card-group>

                <div class="search row mt-2 bg-light p-2" v-if="advanced == false">
                    <div class="col">
                        <multiselect v-model="value" :options="options"></multiselect>
                    </div>
                    <div class="col">
                        <multiselect v-model="value" :options="options"></multiselect>
                    </div>
                    <div class="col">
                        <multiselect v-model="value" :options="options"></multiselect>
                    </div>
                    <div class="col">
                        <multiselect v-model="value" :options="options"></multiselect>
                    </div>
                    <div class="col mt-2" align="right">
                        <i class="fas fa-search text-secondary"></i>
                        <a class="text-primary ml-3" @click="advanced = true">Advanced</a>
                    </div>
                </div>  
                <div v-if="advanced == true" class="search row mt-2 bg-light p-2">
                    <div class="col-10 form-group">
                        <input type="text" class="form-control" placeholder="PMQL">
                    </div>
                    <div class="col mt-2" align="right">
                        <i class="fas fa-search text-secondary"></i>
                        <a class="text-primary ml-3" @click="advanced = false">Basic</a>
                    </div>
                </div>              
            </template>
            <requests-listing ref="requestList" :filter="filter" type="{{ $type }}"></requests-listing>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/requests/index.js')}}"></script>
@endsection

@section('css')
<style>
    .search {
        border: 1px solid rgba(0, 0, 0, 0.125);
        margin-left: -1px;
        margin-right: -1px;
    }
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
        width: 90px;
    }
</style>
@endsection
