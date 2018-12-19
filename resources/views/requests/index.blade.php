@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('content')
<div class="container page-content mt-2" id="requests-listing">
    <h1>{{__($title)}}</h1>
    <div class="row">
        <div class="col-sm-12">
            <template v-if="title">
                <b-card-group deck>

                    <b-card header="<i class='fas fa-id-badge fa-2x'></i>" header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-info mb-3 d-flex flex-row card-border border-0">
                        <a href="{{ route('requests_by_type', ['type' => '']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$startedMe}}</h1>
                            <h6 class="card-text">{{__('Started by Me')}}</h6>
                        </a>
                    </b-card>

                    <b-card header="<i class='fas fa-clipboard-list fa-2x'></i>" header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-success mb-3 d-flex flex-row card-border border-0">
                        <a href="{{ route('requests_by_type', ['type' => 'in_progress']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$inProgress}}</h1>
                            <h6 class="card-text">{{__('In Progress')}}</h6>
                        </a>
                    </b-card>

                    <b-card header="<i class='fas fa-clipboard-check fa-2x'></i>" header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-primary mb-3 d-flex flex-row card-border border-0">
                        <a href="{{ route('requests_by_type', ['type' => 'completed']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$completed}}</h1>
                            <h6 class="card-text">{{__('Complete')}}</h6>
                        </a>
                    </b-card>
                    @if (Auth::user()->is_administrator)
                    <b-card header="<i class='fas fa-clipboard fa-2x'></i>" header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-warning mb-3 d-flex flex-row  card-border border-0">
                        <a href="{{ route('requests_by_type', ['type' => 'all']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$allRequest}}</h1>
                            <h6 class="card-text">{{__('All Request')}}</h6>
                        </a>
                    </b-card>
                    @endif

                </b-card-group>

                <div class="row">
                    <div class="col mt-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                        </div>
                    </div>
                    <div class="col-8" align="right">
                        
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
