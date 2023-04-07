@extends('layouts.layout')

@section('title')
    {{__($title)}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Tasks') => route('tasks.index'),
        __($title) => null,
    ]])
@endsection
@section('content')
  <div class="px-3 page-content mb-0" id="tasks">
    <div class="row">
      <div class="col" align="right">
          <b-alert class="align-middle" show variant="danger" v-cloak v-if="inOverdueMessage.length>0"
            style="text-align: center;" data-cy="tasks-alert">
            @{{ inOverdueMessage }}
          </b-alert>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <div id="search-bar" class="search advanced-search mb-2">
          <div class="d-flex">
            <div class="flex-grow-1">
              <pmql-input
                ref="pmql_input"
                search-type="tasks"
                :value="pmql"
                :url-pmql="urlPmql"
                :filters-value="pmql"
                :ai-enabled="false"
                :show-filters="true"
                :aria-label="$t('Advanced Search (PMQL)')"
                :param-status="status" 
                :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}" 
                @submit="onNLQConversion"
                @filterspmqlchange="onFiltersPmqlChange">

                <template v-slot:left-buttons>
                  <div class="d-flex">
                    <div class="d-flex mr-1" v-for="addition in additions">
                      <component class="d-flex" :is="addition" :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}"></component>
                    </div>
                  </div>
                </template>

              </pmql-input>
            </div>
          </div>
        </div>

        <tasks-list ref="taskList" :filter="filter" :pmql="fullPmql" @in-overdue="setInOverdueMessage"></tasks-list>

      </div>
    </div>
  </div>
@endsection

@section('js')
    <script src="{{mix('js/tasks/index.js')}}"></script>
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
    </style>
@endsection
