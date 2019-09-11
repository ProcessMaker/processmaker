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
<div class="px-3 page-content mt-2" id="requests-listing">
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
                    @can('view-all_requests')
                    <b-card header-class="d-flex align-items-center justify-content-center card-size-header border-0"
                        text-variant="white" class="bg-warning mb-3 d-flex flex-row  card-border border-0">
                        <i slot="header" class='fas fa-clipboard fa-2x'></i>
                        <a href="{{ route('requests_by_type', ['type' => 'all']) }}" class="card-link text-light">
                            <h1 class="m-0 font-weight-bold">{{$allRequest}}</h1>
                            <h6 class="card-text">{{__('All Requests')}}</h6>
                        </a>
                    </b-card>
                    @endcan

                </b-card-group>

                <div id="search-bar" class="search mt-2 bg-light p-2" >
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <div id="search-dropdowns" v-if="! advanced" class="row">
                                <div class="col-3">
                                    <multiselect v-model="process"
                                                 @search-change="getProcesses"
                                                 @input="buildPmql"
                                                 :show-labels="false"
                                                 :loading="isLoading.process"
                                                 open-direction="bottom"
                                                 label="name"
                                                 :options="processOptions"
                                                 :track-by="'id'"
                                                 :multiple="true"
                                                 :placeholder="$t('Process')">
                                        <template slot="noResult">
                                            {{ __('No elements found. Consider changing the search query.') }}
                                        </template>
                                        <template slot="noOptions">
                                            {{ __('No Data Available') }}
                                        </template>
                                        <template slot="selection" slot-scope="{ values, search, isOpen }">
                                            <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('processes') }}</span>
                                        </template>
                                    </multiselect>
                                </div>
                                <div class="col-3">
                                    <multiselect v-model="status"
                                                 :show-labels="false"
                                                 @input="buildPmql"
                                                 :loading="isLoading.status"
                                                 open-direction="bottom"
                                                 label="name"
                                                 :options="statusOptions"
                                                 track-by="value"
                                                 :multiple="true"
                                                 :placeholder="$t('Status')">
                                        <template slot="noResult">
                                            {{ __('No elements found. Consider changing the search query.') }}
                                        </template>
                                        <template slot="noOptions">
                                            {{ __('No Data Available') }}
                                        </template>
                                        <template slot="selection" slot-scope="{ values, search, isOpen }">
                                            <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('statuses') }}</span>
                                        </template>
                                    </multiselect>
                                </div>
                                <div class="col-3">
                                    <multiselect v-model="requester"
                                                 @search-change="getRequesters"
                                                 @input="buildPmql"
                                                 :show-labels="false"
                                                 :loading="isLoading.requester"
                                                 open-direction="bottom"
                                                 label="fullname"
                                                 :options="requesterOptions"
                                                 :track-by="'id'"
                                                 :multiple="true"
                                                 :placeholder="$t('Requester')">
                                        <template slot="noResult">
                                            {{ __('No elements found. Consider changing the search query.') }}
                                        </template>
                                        <template slot="noOptions">
                                            {{ __('No Data Available') }}
                                        </template>
                                        <template slot="selection" slot-scope="{ values, search, isOpen }">
                                            <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('requesters') }}</span>
                                        </template>
                                        <template slot="option" slot-scope="props">
                                            <img v-if="props.option.avatar.length > 0" class="option__image"
                                                 :src="props.option.avatar">
                                            <span v-else class="initials bg-warning text-white p-1"> @{{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                            <span class="ml-1">@{{props.option.fullname}}</span>
                                        </template>
                                    </multiselect>
                                </div>
                                <div class="col-3">
                                    <multiselect v-model="participants"
                                                 @search-change="getParticipants"
                                                 @input="buildPmql"
                                                 :show-labels="false"
                                                 :loading="isLoading.participants"
                                                 open-direction="bottom"
                                                 label="fullname"
                                                 :options="participantsOptions"
                                                 :track-by="'id'"
                                                 :multiple="true"
                                                 :placeholder="$t('Participants')">
                                        <template slot="noResult">
                                            {{ __('No elements found. Consider changing the search query.') }}
                                        </template>
                                        <template slot="noOptions">
                                            {{ __('No Data Available') }}
                                        </template>
                                        <template slot="selection" slot-scope="{ values, search, isOpen }">
                                            <span class="multiselect__single" v-if="values.length > 1 && !isOpen">@{{ values.length }} {{ __('requesters') }}</span>
                                        </template>
                                        <template slot="option" slot-scope="props">
                                            <img v-if="props.option.avatar.length > 0" class="option__image"
                                                 :src="props.option.avatar">
                                            <span v-else class="initials bg-warning text-white p-1"> @{{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                            <span class="ml-1">@{{props.option.fullname}}</span>
                                        </template>
                                    </multiselect>
                                </div>
                            </div>
                            <div id="search-manual" v-if="advanced">
                                <input ref="search_input" type="text" class="form-control" placeholder="PMQL" v-model="pmql">
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div id="search-actions">
                                <div v-if="! advanced">
                                    <b-btn variant="primary" @click="runSearch()" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i></b-btn>
                                    <b-btn variant="secondary" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Advanced Search')"><i class="fas fa-ellipsis-h"></i></b-btn>
                                </div>
                                <div v-if="advanced">
                                    <b-btn variant="primary" @click="runSearch(true)" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i></b-btn>
                                    <b-btn variant="success" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Basic Search')"><i class="fas fa-ellipsis-h"></i></b-btn>
                                </div>
                            </div>
                        </div>
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
    #search-bar {
        height: 59px;
        max-height: 59px;
    }

    #search-manual input {
        border: 1px solid #e8e8e8;
        border-radius: 5px;
        color: gray;
        height: 41px;
    }

    #search-dropdowns {
        padding: 0 11px;
    }

    #search-dropdowns .col-3 {
        padding: 0 4px;
    }

    #search-actions button {
        display: inline-block;
        float: left;
        height: 41px;
        margin-left: 8px;
    }

    .multiselect__placeholder {
        padding-top: 1px;
    }

    .multiselect__single {
        padding-bottom: 2px;
        padding-top: 2px;
    }

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
