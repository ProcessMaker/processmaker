@extends('layouts.layoutnext')

@section('meta')
  <meta name="request-id" content="{{ $task->processRequest->id }}">
@endsection

@section('title')
    {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Tasks') => route('tasks.index'),
        function() use ($task) {
            if ($task->advanceStatus == 'completed') {
                return ['Completed Tasks', route('tasks.index', ['status' => 'CLOSED'])];
            }
            return ['To Do Tasks', route('tasks.index')];
        },
        $task->processRequest->name =>
            Auth::user()->can('view', $task->processRequest) ? route('requests.show', ['request' => $task->processRequest->id]) : null,
        '@{{taskTitle}}' => null,
      ], 'attributes' => 'v-cloak'])
@endsection
@section('content')
<div
  id="task"
  v-cloak
>
  <div class="menu-mask" :class="{ 'menu-open': showMenu }"></div>
  <div class="info-main" :class="{ 'menu-open': showMenu }">
    <div
      class="tw-flex tw-w-full tw-grow px-3">
      <div class="tw-flex tw-w-full tw-grow">
        <div class="tw-flex tw-flex-col tw-grow tw-overflow-hidden container-height">
          <div 
            v-if="isSelfService"
            class="alert alert-primary"
            role="alert">
            <button 
              type="button"
              class="btn btn-primary"
              @click="claimTask">{{__('Claim Task')}}
            </button>
            {{__('This task is unassigned, click Claim Task to assign yourself.')}}
          </div>
          <div
            id="interactionListener"
            class="tw-flex tw-flex-col tw-grow tw-overflow-hidden">
            @can('editData', $task->processRequest)
              <ul
                v-if="task.process_request.status === 'ACTIVE'"
                id="tabHeader"
                role="tablist"
                class="nav nav-tabs">
                <li class="nav-item">
                  <a
                    id="pending-tab"
                    data-toggle="tab"
                    href="#tab-form"
                    role="tab"
                    aria-controls="tab-form"
                    aria-selected="true"
                    class="nav-link active">
                    {{__('Form')}}
                  </a>
                </li>
                <li class="nav-item">
                  <a
                    id="summary-tab"
                    data-toggle="tab"
                    href="#tab-data"
                    role="tab"
                    aria-controls="tab-data"
                    aria-selected="false"
                    @click="resizeMonaco"
                    class="nav-link">
                    {{__('Data')}}
                  </a>
                </li>
              </ul>
            @endcan
            <div id="tabContent" class="tab-content tw-flex tw-flex-col tw-grow tw-overflow-y-scroll">
              <div id="tab-form" role="tabpanel" aria-labelledby="tab-form" class="tab-pane active show">
                @can('update', $task)
                <task
                  ref="task"
                  class="card border-0"
                  v-model="formData"
                  :initial-task-id="{{ $task->id }}"
                  :initial-request-id="{{ $task->process_request_id }}"
                  :screen-version="{{ $task->screen['id'] ?? null }}"
                  :user-id="{{ Auth::user()->id }}"
                  csrf-token="{{ csrf_token() }}"
                  initial-loop-context="{{ $task->getLoopContext() }}"
                  :wait-loading-listeners="true"
                  @task-updated="taskUpdated"
                  @updated-page-core="updatePage"
                  @submit="submit"
                  @completed="completed"
                  @@error="error"
                  @closed="closed"
                  @redirect="redirectToTask"
                  @form-data-changed="handleFormDataChange" />
                @endcan
                <div v-if="taskHasComments">
                  <timeline :commentable_id="task.id"
                    commentable_type="ProcessMaker\Models\ProcessRequestToken"
                    :adding="false"
                    :readonly="task.status === 'CLOSED'"
                    :timeline="false" />
                </div>
              </div>
              @can('editData', $task->processRequest)
              <div v-if="task.process_request.status === 'ACTIVE'" id="tab-data" role="tabpanel" aria-labelledby="tab-data" class="card card-body border-top-0 tab-pane p-3">
                <!-- data edit -->
                  <monaco-editor
                      v-show="!showTree"
                      ref="monaco"
                      data-cy="editorViewFrame"
                      :options="monacoLargeOptions"
                      v-model="jsonData"
                      language="json"
                      style="border:1px solid gray; min-height:700px;"
                  ></monaco-editor>

                  <tree-view v-if="showTree" v-model="jsonData" style="border:1px; solid gray; min-height:700px;"></tree-view>

                  <div class="d-flex justify-content-between mt-3">
                      <data-tree-toggle v-model="showTree"></data-tree-toggle>
                      <span>
                          @isset($dataActionsAddons)
                              @foreach ($dataActionsAddons as $dataActionsAddon)
                                  {!! $dataActionsAddon['content'] ?? '' !!}
                              @endforeach
                          @endisset
                          <button type="button" class="btn btn-secondary" @click="updateRequestData()">
                              {{__('Save')}}
                          </button>
                      </span>
                  </div>
              </div>
              @endcan
            </div>
          </div>
        </div>
        @if (shouldShow('taskStatusContainer'))
        <div class="slide-control">
          <a href="#" @click="hideMenu">
            <i class="fa" :class="{ 'fa-caret-left' : !showMenu, 'fa-caret-right' : showMenu }"></i>
          </a>
        </div>
        <div class="menu">
          <ul class="nav nav-tabs nav-collapse" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                id="details-tab"
                :class="{'nav-link': true, active: showInfo }"
                data-bs-toggle="tab"
                data-bs-target="#details"
                type="button"
                role="tab"
                aria-controls="details"
                aria-selected="true"
                @click="switchTabInfo('details')">
                {{ __('Details') }}
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                id="comments-tab"
                :class="{'nav-link': true, active: !showInfo }"
                data-bs-toggle="tab"
                data-bs-target="#comments"
                type="button"
                role="tab"
                aria-controls="comments"
                aria-selected="false"
                @click="switchTabInfo('comments')">
                {{ __('Comments') }}
              </button>
            </li>
          </ul>
          <div class="menu-tab-content">
            <div id="collapse-info" class="collapse show width">
              <div
                v-if="showInfo"
                id="details"
                v-bind:class="{ 'tab-pane':true, show: showInfo, active: showInfo }"
                role="tabpanel"
                aria-labelledby="details-tab">
                <div class="ml-md-3 mt-3 mt-md-0">
                  <div class="card collapse-content">
                    <ul class="list-group list-group-flush w-100 tw-overflow-y-auto">
                      <li class="list-group-item">
                        <div
                          v-if="taskDraftsEnabled"
                          class="row justify-content-start pb-1">
                          <task-save-notification
                            :options="options"
                            :task="task"
                            :date="lastAutosaveNav"
                            :error="errorAutosave"
                            :form-data="formData" />
                        </div>
                        <div class="row button-group">
                          <div class="col-6">
                            <button
                              type="button"
                              class="btn btn-block button-actions"
                              @click="createRule">
                              <i class="fas fa-plus"></i> {{ __('Create Rule') }}
                            </button>
                          </div>
                          <div class="col-6">
                            <button
                              type="button"
                              class="btn btn-block button-actions"
                              :class="{ 'button-priority': isPriority }"
                              @click="addPriority">
                              <img
                                :src="
                                        isPriority
                                          ? '/img/priority.svg'
                                          : '/img/priority-header.svg'
                                      "
                                alt="'No Image'">
                              {{ __('Priority') }}
                            </button>
                          </div>
                        </div>
                        <div class="row button-group">
                          <div class="col-6">
                            <template>
                              <button
                                type="button"
                                v-b-tooltip.hover title="Use content from previous task to fill this one quickly."
                                class="btn btn-block button-actions"
                                @click="showQuickFill">
                                <img
                                  src="../../img/smartinbox-images/fill.svg"
                                  alt="{{__('No Image')}}" /> {{__('Quick Fill')}}
                              </button>
                            </template>
                          </div>
                          <div class="col-6">
                            <template v-if="allowReassignment">
                              <button
                                v-if="task.advanceStatus === 'open' || task.advanceStatus === 'overdue'"
                                type="button"
                                class="btn btn-block button-actions"
                                @click="show">
                                <i class="fas fa-user-friends"></i> {{__('Reassign')}}
                              </button>
                            </template>
                          </div>
                        </div>
                      </li>
                      <li class="list-group-item">
                        <!-- Section to Add Now What? -->
                        <button
                          type="button"
                          class="btn btn-block button-actions"
                          @click="eraseDraft()"
                          v-if="taskDraftsEnabled">
                          <img src="/img/smartinbox-images/eraser.svg" alt="{{__('No Image')}}">
                          {{ __('Clear Draft') }}
                        </button>
                      </li>
                      <div :class="statusCard">
                        <span style="margin:0; padding:0; line-height:1">@{{$t(task.advanceStatus)}}</span>
                      </div>
                      <li v-if="dateDueAt && showDueAtDates" class="list-group-item">
                        <p class="section-title">@{{$t(dueLabel)}} @{{ moment(dateDueAt).fromNow() }}</p>
                        @{{ moment(dateDueAt).format() }}
                      </li>
                      <li v-if="!showDueAtDates" class="list-group-item">
                        <p class="section-title">@{{$t(dueLabel)}} @{{ moment().to(moment(completedAt)) }}</p>
                        @{{ moment(completedAt).format() }}
                      </li>
                      <li class="list-group-item" v-if="taskDraftsEnabled">
                        <task-save-panel
                          :options="options"
                          :task="task"
                          :date="lastAutosave"
                          :error="errorAutosave" />
                      </li>
                      <li v-if="task.is_self_service === 0" class="list-group-item">
                        <p class="section-title">{{__('Assigned To')}}:</p>
                        <avatar-image
                          v-if="task.user"
                          size="32"
                          class="d-inline-flex pull-left align-items-center"
                          :input-data="task.user"></avatar-image>
                        @isset($assignedToAddons)
                        @foreach ($assignedToAddons as $addon)
                        {!! $addon['content'] ?? '' !!}
                        @endforeach
                        @endisset
                      </li>
                      <li class="list-group-item">
                        <p class="section-title"> {{__('Assigned') }} @{{ moment(createdAt).fromNow() }}</p>
                        @{{ moment(createdAt).format() }}
                      </li>
                      <li class="list-group-item">
                        <p class="section-title">{{__('Case')}}</p>
                        @{{ caseTitle }}
                        <!-- This is the name defined in the installation of connector-docusign 'DocuSignAuthentication' -->
                        @if ($task->process->name !== 'DocuSignAuthentication')
                          <p class="launchpad-link">
                            <a href="{{route('process.browser.index', [$task->process->id])}}">
                              {{ __('Open Process Launchpad') }}
                            </a>
                          </p>
                        @endif
                      </li>
                      <li class="list-group-item">
                        <p class="section-title">{{__('Request')}}</p>
                        <a href="{{route('requests.show', [$task->process_request_id, 'skipInterstitial' => '1'])}}"
                          data-test="request-link"
                        >
                          #{{$task->process_request_id}} {{$task->process->name}}
                        </a>
                      </li>
                      <li class="list-group-item">
                        <p class="section-title">{{__('Requested By')}}:</p>
                        <avatar-image
                          v-if="task.requestor"
                          size="32"
                          class="d-inline-flex pull-left align-items-center"
                          :input-data="task.requestor"></avatar-image>
                        <p v-if="!task.requestor">{{__('Web Entry')}}</p>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div
                v-if="!showInfo"
                id="comments"
                v-bind:class="{ 'tab-pane':true, show: !showInfo, active: !showInfo }"
                role="tabpanel"
                aria-labelledby="comments-tab">
                <div class="ml-md-3 mt-md-0 mt-3 collapse-content">
                  <template v-if="panCommentInVueOptionsComponents">
                    <comment-container
                      commentable_type="ProcessMaker\Models\ProcessRequestToken"
                      :commentable_id="task.id"
                      :readonly="task.status === 'CLOSED'"
                      :name="task.element_name"
                      :header="false"
                      :case_number="task.process_request.case_number"
                      :get-data="getCommentsData" />
                  </template>
                </div>
              </div>
            </div>
          </div>
          <b-modal
            v-model="showReassignment"
            size="md"
            centered title="{{'Reassign to'}}"
            header-close-content="&times;"
            v-cloak
            @hide="cancelReassign">
            <div class="form-group">
              {{ html()->label(__('User'), 'user') }}
              <p-m-dropdown-suggest v-model="selectedUser"
                :options="reassignUsers"
                @pmds-input="onReassignInput"
                placeholder="{{__('Type here to search')}}">
                <template v-slot:pre-text="{ option }">
                  <b-badge variant="secondary"
                    class="mr-2 custom-badges pl-2 pr-2 rounded-lg">
                    @{{ option.active_tasks_count }}
                  </b-badge>
                </template>
              </p-m-dropdown-suggest>
            </div>
            <div slot="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="cancelReassign">
                {{__('Cancel')}}
              </button>
              <button type="button" class="btn btn-secondary" @click="reassignUser(true)" :disabled="disabled">
                {{__('Reassign')}}
              </button>
            </div>
          </b-modal>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
@include('shared.monaco')
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vue-vendor.js') }}"></script>
<script src="{{ mix('js/bootstrap-vendor.js') }}"></script>
<script src="{{ mix('js/fortawesome-vendor.js') }}"></script>
<script >
  window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  const screenBuilderScripts = @json($manager->getScripts());
</script>
<script src="{{ mix('js/tasks/loaderEdit.js')}}"></script>
  <script>
    window.ProcessMaker.EventBus.$on("screen-renderer-init", (screen) => {
      if (screen.watchers_config) {
        screen.watchers_config.api.execute = @json(route('api.scripts.execute', ['script_id' => 'script_id', 'script_key' => 'script_key']));
        screen.watchers_config.api.execution = @json(route('api.scripts.execution', ['key' => 'execution_key']));
      } else {
        console.warn('Screen builder version does not have watchers');
      }
    });

    window.PM4ConfigOverrides = {
      requestFiles: @json($files),
      getScreenEndpoint: 'tasks/{{ $task->id }}/screens',
      postScriptEndpoint: '/scripts/execute/{id}?task_id={{ $task->id }}',
    };

    window.sessionStorage.setItem(
      'elementDestinationURL',
      '{{ route('requests.show', ['request' => $task->process_request_id]) }}'
    );

    const task = @json($task);
    let draftTask = task.draft;
    const userHasAccessToTask = {{ Auth::user()->can('update', $task) ? "true": "false" }};
    const userIsAdmin = {{ Auth::user()->is_administrator ? "true": "false" }};
    const userIsProcessManager = {{ Auth::user()->id === $task->process?->manager_id ? "true": "false" }};
    const userConfiguration = @json($userConfiguration);
    let screenFields = @json($screenFields);
    window.Processmaker.user = @json($currentUser);
    window.ProcessMaker.taskDraftsEnabled = @json($taskDraftsEnabled);
  </script>

  @foreach(GlobalScripts::getScripts() as $script)
    <script src="{{$script}}"></script>
  @endforeach

  <script src="{{mix('js/tasks/edit.js')}}"></script>
@endsection

@section('css')
<link href="{{ mix('css/collapseDetails.css') }}" rel="stylesheet">
<style>
  .menu-tab-content {
    margin-left: -16px;
  }
  .inline-input {
    margin-right: 6px;
  }
  .inline-button {
    background-color: rgb(109, 124, 136);
    font-weight: 100;
  }
  .input-and-select {
    width: 212px;
  }
  .multiselect__element span img {
    border-radius: 50%;
    height: 20px;
  }
  .multiselect__tags-wrap img {
    height: 15px;
    border-radius: 50%;
  }
  .multiselect__tag-icon:after {
    color: white !important;
  }
  .multiselect__option--highlight {
    background: #00bf9c !important;
  }
  .multiselect__option--selected.multiselect__option--highlight {
    background: #00bf9c !important;
  }
  .multiselect__tag {
    background: #788793 !important;
  }
  .multiselect__tag-icon:after {
    color: white !important;
  }
  .btn-outline-custom {
    border-radius: 4px;
    border: 1px solid var(--borders, #CDDDEE);
    background: linear-gradient(180deg, #fff 0%, rgb(255 255 255 / 0%) 100%), #fbfbfb;
    box-shadow: 0px 0px 3px -2px rgba(0, 0, 0, 0.10);
    color: var(--text-only, #556271);
  }
  .text-status {
    display: flex;
    height: 48px;
    padding: 12px 16px;
    align-items: center;
    gap: 16px;
    margin: 16px;
    color: white;
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 700;
    line-height: 22px;
    letter-spacing: -0.02em;
    text-align: left;
    text-transform: uppercase;
  }
  .nav-collapse {
    border: none;
  }
  .section-title {
    color: var(--text-only, #556271);
    font-size: 14px;
    font-style: normal;
    font-weight: 700;
    line-height: 150%;
    letter-spacing: -0.28px;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }
  .open-style {
    background-color: #4ea075;
  }
  .overdue-style {
    background-color: #ed4858;
  }
  .button-actions {
    color: #556271;
    text-transform: capitalize;
    font-family: Open Sans;
    font-size: 15px;
    font-weight: 400;
    line-height: 24px;
    letter-spacing: -0.02em;
    text-align: left;
    border: 1px solid #CDDDEE;
    border-radius: 4px;
    box-shadow: 0px 0px 3px 0px #0000001a;
  }
  .button-actions:hover {
    color: #556271;
    background-color: #f3f5f8;
  }
  .button-group {
    margin-top: 10px;
    margin-bottom: 10px;
  }
  .button-priority {
    background-color: #FEF2F3;
    color: #C56363;
  }
  .card-header:first-child.text-status {
    border-radius: 6px;
  }
  .launchpad-link {
    margin-top: 5px;
  }
  .container-height {
    height: calc(100vh - 145px);
  }
</style>
@endsection
