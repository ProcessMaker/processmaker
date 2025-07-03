@extends('layouts.layout')

@section('title')
    {{ __($title) }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', [
        'routes' => [
            Route::currentRouteName() === 'inbox' ? __('Inbox') : __('Tasks') => Route::currentRouteName() === 'inbox' ? route('inbox') : route('tasks.index')
        ],
    ])
@endsection

@section('content')
    <div id="tasks">
        <div v-if="showOldTaskScreen" class="process-catalog-main" :class="{ 'menu-open': showMenu }">
            <div class="px-3 page-content mb-0">
                <div class="row">
                    <div class="col" align="right">
                        <b-alert v-if="inOverdueMessage.length>0" class="align-middle" show variant="danger" v-cloak
                            style="text-align: center;" data-cy="tasks-alert">
                            @{{ inOverdueMessage }}
                        </b-alert>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <ul class="nav nav-tabs task-nav" id="requestTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link task-nav-link" id="inbox-tab" :data-toggle="isDataLoading ? '' : 'tab'"
                                    href="#" role="tab" aria-controls="inbox"
                                    @click.prevent="!isDataLoading ? switchTab('inbox') : null" aria-selected="true"
                                    :class="{ 'active': tab === 'inbox' }">
                                    {{ __('Inbox') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link task-nav-link" id="priority-tab"
                                    :data-toggle="isDataLoading ? '' : 'tab'" href="#" role="tab"
                                    aria-controls="inbox" @click.prevent="!isDataLoading ? switchTab('priority') : null"
                                    aria-selected="true" :class="{ 'active': tab === 'priority' }">
                                    {{ __('Priority') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link task-nav-link" id="drafts-tab" :data-toggle="isDataLoading ? '' : 'tab'"
                                    href="#" role="tab" aria-controls="inbox"
                                    @click.prevent="!isDataLoading ? switchTab('draft') : null" aria-selected="true"
                                    :class="{ 'active': tab === 'draft' }" v-if="taskDraftsEnabled">
                                    {{ __('Drafts') }}
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="task-tabContent">
                            <div class="tab-pane fade show active" id="inbox" role="tabpanel"
                                aria-labelledby="inbox-tab">
                                <div class="card card-body task-list-body">
                                    <div id="search-bar" class="search advanced-search mb-2">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <pmql-input ref="pmql_input" search-type="tasks" :value="pmql"
                                                    :url-pmql="urlPmql" :filters-value="pmql" :ai-enabled="false"
                                                    :show-filters="true" :aria-label="$t('Advanced Search (PMQL)')"
                                                    :param-status="status"
                                                    :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}"
                                                    @submit="onNLQConversion" @filterspmqlchange="onFiltersPmqlChange">

                                                    <template v-slot:left-buttons>
                                                        <div class="d-flex">
                                                            <div class="d-flex mr-1" v-for="addition in additions">
                                                                <component class="d-flex" :is="addition"
                                                                    :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}">
                                                                </component>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <template v-slot:right-buttons>
                                                        <b-button id="idPopoverInboxRules" class="ml-md-1 task-inbox-rules"
                                                            variant="primary" @click="onInboxRules">
                                                            {{ __('Inbox Rules') }}
                                                        </b-button>
                                                        <b-popover target="idPopoverInboxRules" triggers="hover focus"
                                                            placement="bottomleft">
                                                            <div class="task-inbox-rules-content">
                                                                <div>
                                                                    <img src="/img/inbox-rule-suggest.svg"
                                                                        alt="{{ __('Inbox Rules') }}">
                                                                </div>
                                                                <span class="task-inbox-rules-content-text">
                                                                    <!-- //NOSONAR -->{!! __(
                                                                        'Inbox Rules act as your personal task manager. You tell them what to look for, and they <strong>take care of things automatically.</strong>',
                                                                    ) !!}
                                                                </span>
                                                            </div>
                                                        </b-popover>
                                                        @if (
                                                            (Auth::user()->is_administrator || Auth::user()->hasPermission('edit-screens')) &&
                                                                Route::has('package.savedsearch.defaults.edit'))
                                                            <b-button class="ml-md-2"
                                                                href="{{ route('package.savedsearch.defaults.edit', [
                                                                    'type' => 'task',
                                                                    'key' => 'tasks',
                                                                ]) }}">
                                                                <i class="fas fw fa-cog"></i>
                                                            </b-button>
                                                        @endif
                                                    </template>
                                                </pmql-input>
                                            </div>
                                        </div>
                                    </div>
                                    <tasks-list ref="taskList" :filter="filter" :pmql="fullPmql"
                                        :columns="columns" :disable-tooltip="false"
                                        :disable-quick-fill-tooltip="false" :fetch-on-created="false"
                                        @in-overdue="setInOverdueMessage" @data-loading="dataLoading"
                                        @tab-count="handleTabCount" @on-fetch-task="onFetchTask"
                                        :show-recommendations="true"></tasks-list>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <participant-home-screen :task-drafts-enabled="{{ json_encode($taskDraftsEnabled) }}"
                :user-filter="{{ json_encode($userFilter) }}"
                :default-columns="{{ json_encode($defaultColumns ?? []) }}"
                :user-configuration="{{ $userConfiguration ?? [] }}"
                :user-permissions="{{ json_encode([
                    'hasPermissionsForUsersGroups' => Auth::user()->hasPermissionsFor('users', 'groups'),
                    'isAdministrator' => Auth::user()->is_administrator,
                    'canEditScreens' => Auth::user()->hasPermission('edit-screens'),
                ]) }}"
                :savedsearch-defaults-edit-route="{{ Route::has('package.savedsearch.defaults.edit') ? json_encode(
                    route('package.savedsearch.defaults.edit', [
                        'type' => 'task',
                        'key' => 'tasks',
                    ])
                ) : 'null' }}"></participant-home-screen>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.ProcessMaker.taskDraftsEnabled = @json($taskDraftsEnabled);
        window.ProcessMaker.advanced_filter = @json($userFilter);
        window.Processmaker.defaultColumns = @json($defaultColumns);
        window.ProcessMaker.isDefaultColumns = @json($isDefaultColumns ?? false);
        window.ProcessMaker.userConfiguration = @json($userConfiguration ?? []);
        window.sessionStorage.setItem('elementDestinationURL', window.location.href);
        window.ProcessMaker.showOldTaskScreen = @json($showOldTaskScreen);
        window.Processmaker.user = @json($currentUser);
        window.Processmaker.selectedProcess = @json($selectedProcess);
        window.Processmaker.defaultSavedSearchId = @json($defaultSavedSearchId);
    </script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script>
        window.ProcessMaker.ellipsisPermission = {{
          Js::from(\Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks', 'projects', 'documentation'))
        }};
      </script>
    <script src="{{ mix('js/tasks/index.js') }}"></script>
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

        .option__image {
            width: 27px;
            height: 27px;
            border-radius: 50%;
        }

        .initials {
            display: inline-block;
            text-align: center;
            font-size: 12px;
            max-width: 25px;
            max-height: 25px;
            min-width: 25px;
            min-height: 25px;
            border-radius: 50%;
        }

        .task-nav {
            border-bottom: 0 !important;
        }

        .task-nav-link.active {
            color: #1572C2 !important;
            font-weight: 700;
            font-size: 15px;
        }

        .task-nav-link {
            color: #556271;
            font-weight: 400;
            font-size: 15px;
            border-top-left-radius: 5px !important;
            border-top-right-radius: 5px !important;
        }

        .task-list-body {
            border-radius: 5px;
        }

        .task-inbox-rules {
            width: max-content;
        }

        .task-inbox-rules-content {
            display: flex;
            justify-content: space-between;
            padding: 15px;
        }

        .task-inbox-rules-content-text {
            width: 310px;
            padding-left: 10px;
        }
    </style>
    <style scoped>
        .popover {
            max-width: 450px;
        }
    </style>
    <style scoped lang="scss">
        /* @import '~styles/variables'; */

        @media (max-width: 639px) {
            .breadcrum-main {
                display: none;
            }
        }

        .process-catalog-main {
            display: block;
        }

        .process-catalog-main-participant > .menu {
            left: -100%;
            height: calc(100vh - 145px);
            overflow: hidden;
            margin-top: 15px;
            transition: all 0.3s;
            flex: 0 0 0px;
            background-color: #F7F9FB;

            .menu-catalog {
                background-color: #F7F9FB;
                flex: 1;
                width: 315px;
                height: 95%;
                overflow-y: scroll;
            }

            @media (max-width: 639px) {
                position: absolute;
                z-index: 4;
                display: flex;
                margin-top: 0;
                width: 85%;
                transition: left 0.3s;
            }
        }

        .menu-mask {
            display: none;
            position: absolute;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0);
            z-index: 3;
            transition: background-color 0.3s;

            @media (max-width: 639px) {
                display: block;
            }
        }

        .menu-mask.menu-open {
            @media (max-width: 639px) {
                left: 0;
                background-color: rgba(0, 0, 0, 0.5);
                display: block;
            }
        }

        .menu-open .menu {
            left: 0;
            flex: 0 0 250px;

            @media (max-width: 639px) {
                left: 0%;
            }
        }

        .mobile-slide-close {
            display: none;
            padding-left: 10px;
            padding-top: 10px;

            @media (max-width: 639px) {
                display: block;
            }
        }

        .slide-control {
            border-left: 1px solid #DEE0E1;
            margin-left: 10px;
            width: 29px;

            @media (max-width: 639px) {
                display: none;
            }

            a {
                position: relative;
                left: -11px;
                top: 40px;
                z-index: 5;

                display: flex;
                align-items: center;
                justify-content: center;
                width: 20px;
                height: 60px;
                background-color: #ffffff;
                border-radius: 10px;
                border: 1px solid #DEE0E1;
                color: #6A7888;
            }
        }

        .menu-open .slide-control {
            border-left: 1px solid #DEE0E1;

            a {
                left: -11px;
                display: none;
            }

        }

        .slide-control:hover {
            border-left: 1px solid rgba(72, 145, 255, 0.40);
            box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);
        }

        .menu-open .slide-control:hover {
            border-left: 1px solid rgba(72, 145, 255, 0.40);
            box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);

            a {
                display: flex;
            }
        }

        .slide-control a:hover {
            background-color: #EAEEF2;
        }

        .mobile-menu-control {
            display: none;
            color: #6A7887;
            font-size: 1.3em;
            margin-top: 10px;
            margin-left: 1em;
            margin-right: 1em;
            align-items: center;

            .menu-button {
                flex-grow: 1;

                i {
                    margin-right: 3px;
                }
            }

            .bookmark-button {
                display: flex;
                padding: 10px;
                margin-right: 10px;
                font-size: 1.1em;
            }

            .search-button {
                display: flex;
                padding: 10px;
                font-size: 1.1em;
            }

            @media (max-width: 639px) {
                display: flex;
            }
        }

        .menu-title {
            color: #556271;
            font-size: 22px;
            font-style: normal;
            font-weight: 600;
            line-height: 46.08px;
            letter-spacing: -0.44px;
            display: block;
            width: 92%;
            margin-left: 15px;

            @media (max-width: 639px) {
                display: none;
            }
        }

        .processes-info {
            width: 100%;
            margin-right: 0px;
            overflow-x: hidden;

            @media (max-width: 639px) {
                padding-left: 0;
            }
        }
    </style>
@endsection
