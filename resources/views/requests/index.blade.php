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
                        <multiselect 
                        v-model="process" 
                        @search-change="getProcesses" 
                        @input="buildPmql"
                        :select-label="''" 
                        :loading="isLoading.process" 
                        open-direction="bottom" 
                        label="name" 
                        :options="processOptions"
                        :track-by="'id'"
                        :multiple="true" 
                        :limit="1" 
                        :limit-text="count => `+${count}`" 
                        placeholder="Process"></multiselect>
                    </div>
                    <div class="col">
                        <multiselect
                        v-model="status"
                        :select-label="''" 
                        @input="buildPmql"
                        :loading="isLoading.status"
                        open-direction="bottom"
                        label="name"
                        :options="statusOptions"
                        track-by="value"
                        :multiple="true"
                        :limit="1"
                        :limit-text="count => `+${count}`"
                        placeholder="Status"></multiselect>
                    </div>
                    <div class="col">
                        <multiselect 
                        v-model="requester" 
                        @search-change="getRequesters" 
                        @input="buildPmql"
                        :select-label="''" 
                        :loading="isLoading.requester" 
                        open-direction="bottom" 
                        label="fullname" 
                        :options="requesterOptions" 
                        :track-by="'id'"
                        :multiple="true" 
                        :limit="1" 
                        :limit-text="count => `+${count}`" 
                        placeholder="Requester">
                            <template slot="option" slot-scope="props">
                                <img v-if="props.option.avatar.length > 0" class="option__image" :src="props.option.avatar">
                            <span v-else class="initials bg-warning text-white p-1"> @{{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                <span class="ml-1">@{{props.option.fullname}}</span>
                            </template>
                        </multiselect>
                    </div>
                    <div class="col">
                        <multiselect 
                        v-model="participants" 
                        :options="participantsOptions" 
                        :select-label="''" 
                        :loading="isLoading.participants" 
                        group-values="items" 
                        group-label="label" 
                        :group-select="true"
                        @search-change="getParticipants" 
                        @input="buildPmql"
                        :multiple="true" 
                        :track-by="'track'"
                        open-direction="bottom" 
                        label="name" 
                        :limit="1" 
                        :limit-text="count => `+${count}`" 
                        placeholder="Participants">
                            <template slot="option" slot-scope="props">
                                <span v-if="props.option.$isLabel">
                                    <span>@{{props.option.$groupLabel}}</span>
                                </span>
                                <span v-if="props.option.username">
                                    <img v-if="props.option.avatar && props.option.avatar.length > 0" class="option__image" :src="props.option.avatar">
                                    <span v-else class="initials bg-warning text-white p-1">@{{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                    <span class="ml-1">@{{props.option.name}}</span>
                                </span>
                                <span v-else>
                                    <span class="ml-1">@{{props.option.name}}</span>
                                </span>
                            </template>
                    </multiselect>
                    </div>
                    <div class="col mt-2" align="right">
                        <button class="btn btn-default" @click="runSearch"><i class="fas fa-search text-secondary"></i></button>
                        <a class="text-primary ml-3" @click="advanced = true">{{__('Advanced')}}</a>
                    </div>
                </div>  
                <div v-if="advanced == true" class="search row mt-2 bg-light p-2">
                    <div class="col-10 form-group">
                        <input type="text" class="form-control" placeholder="PMQL" v-model="pmql">
                    </div>
                    <div class="col mt-2" align="right">
                        <i class="fas fa-search text-secondary"></i>
                        <a class="text-primary ml-3" @click="advanced = false">{{__('Basic')}}</a>
                    </div>
                </div>              
            </template>
            <requests-listing ref="requestList"></requests-listing>
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
