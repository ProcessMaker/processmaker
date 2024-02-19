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
            <a class="nav-link task-nav-link active" id="inbox-tab" data-toggle="tab" href="#inbox" role="tab"
              aria-controls="inbox" aria-selected="true">
              {{ __('Inbox') }}
            </a>
          </li>
        </ul>

        <div class="tab-content" id="task-tabContent">
          <div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">
            <div class="card card-body task-list-body">
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

                      <template v-slot:right-buttons>
                          <b-button id="idPopoverInboxRules"
                                    class="ml-md-1 task-inbox-rules"
                                    variant="primary"
                                    @click="onInboxRules">
                            {{ __('Inbox Rules') }}
                          </b-button>
                          <b-popover target="idPopoverInboxRules" 
                                     triggers="hover focus"
                                     placement="bottomleft">
                            <div class="task-inbox-rules-content">
                              <div>
                                <svg width="59" height="59" viewBox="0 0 59 59" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M18.8133 57.481C22.9108 59.9507 25.8339 58.9254 26.953 56.945C29.1171 53.1151 36.3128 45.2832 47.7159 42.6362C62.8186 39.1302 59.6646 29.8214 50.3804 24.9365C41.0961 20.0517 34.1541 13.2926 35.2048 5.94325C36.2554 -1.40607 25.8453 -3.93962 21.2157 10.198C18.224 19.3343 12.8626 21.7819 9.4438 22.3703C6.22161 22.9248 2.69694 23.6205 0.978136 26.4018C-1.10752 29.7765 -0.379724 33.1901 8.35244 35.5315C20.7268 38.8494 9.62918 51.945 18.8133 57.481Z" fill="#DEEFFF"/>
                                  <path d="M23.3148 53.9492C21.5617 55.7023 18.7117 55.7023 16.9492 53.9492L8.31484 45.3055C6.56172 43.5523 6.56172 40.7023 8.31484 38.9398L13.068 34.1867L16 37.1187C16.5812 37.7 17.5375 37.7 18.1187 37.1187C18.7 36.5375 18.7 35.5812 18.1187 35L15.1867 32.068L19.068 28.1867L23.568 32.6867C24.1492 33.268 25.1055 33.268 25.6867 32.6867C26.268 32.1055 26.268 31.1492 25.6867 30.568L21.1867 26.068L25.068 22.1867L28 25.1187C28.5812 25.7 29.5375 25.7 30.1187 25.1187C30.7 24.5375 30.7 23.5812 30.1187 23L27.1867 20.068L31.068 16.1867L35.568 20.6867C36.1492 21.268 37.1055 21.268 37.6867 20.6867C38.268 20.1055 38.268 19.1492 37.6867 18.568L33.1867 14.068L37.9398 9.31484C39.693 7.56172 42.543 7.56172 44.3055 9.31484L52.9398 17.9492C54.693 19.7023 54.693 22.5523 52.9398 24.3148L23.3148 53.9492Z" fill="#1572C2"/>
                                </svg>
                              </div>
                              <span class="task-inbox-rules-content-text">
                              {{ __('Inbox Rules act as your personal task manager. You tell them what to look for, and they') }}
                              <strong>{{ __('take care of things automatically.') }}</strong>
                              </span>
                            </div>
                          </b-popover>
                          @if((
                              Auth::user()->is_administrator ||
                              Auth::user()->hasPermission('edit-screens')
                            ) && Route::has('package.savedsearch.defaults.edit'))
                          <b-button
                            class="ml-md-2"
                            href="{{route(
                              'package.savedsearch.defaults.edit',
                              [
                                'type'=>'task',
                                'key'=>'tasks',
                              ]
                            )}}"
                          >
                            <i class="fas fw fa-cog"></i>
                          </b-button>
                          @endif
                      </template>
                    </pmql-input>
                  </div>
                </div>
              </div>
              <tasks-list
                ref="taskList"
                :filter="filter"
                :pmql="fullPmql"
                :columns="columns"
                @in-overdue="setInOverdueMessage"
              ></tasks-list>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
@endsection

@section('js')
    <script>
      window.ProcessMaker.advanced_filter = @json($userFilter);
      window.Processmaker.defaultColumns = @json($defaultColumns);
    </script>
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
      .popover{
        max-width: 450px;
      }
    </style>
@endsection
