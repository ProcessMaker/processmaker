@extends('layouts.layout')

@section('title')
  {{ __('Request Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('meta')
  <meta name="request-id" content="{{ $request->id }}">
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', [
      'routes' => [
          __('Requests') => route('requests.index'),
          $request->name . ' #' . $request->getKey() => null,
      ],
      'dynamic' => true,
  ])
@endsection
@section('content')
  <div id="request">
    <div class="menu-mask" :class="{ 'menu-open': showMenu }"></div>
    <div class="info-main" :class="{ 'menu-open': showMenu }">
      <div class="container-fluid px-3">
        <div class="d-flex flex-column flex-md-row">
          <div class="flex-grow-1">
            <div class="container-fluid">
              <ul class="nav nav-tabs" id="requestTab" role="tablist">
                <template v-if="status">
                  @if ($request->status === 'ERROR')
                    <li class="nav-item">
                      <a class="nav-link active" id="errors-tab" data-toggle="tab" href="#errors" role="tab"
                        @click="switchTab('errors')" aria-controls="errors" aria-selected="false">{{ __('Errors') }}</a>
                    </li>
                  @endif
                  <li class="nav-item" v-if="showTasks">
                    <a class="nav-link" :class="{ active: activePending }" id="pending-tab" data-toggle="tab"
                      @click="switchTab('pending')" href="#pending" role="tab" aria-controls="pending"
                      aria-selected="true">{{ __('Tasks') }}</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="overview-tab" data-toggle="tab" href="#overview" role="tab"
                      aria-controls="overview" aria-selected="false" @click="switchTab('overview')">
                      {{ __('Overview') }}
                    </a>
                  </li>
                  <li class="nav-item" v-if="showSummary">
                    <a id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary"
                      @click="switchTab('summary')" aria-selected="false"
                      v-bind:class="{ 'nav-link':true, active: showSummary && !activePending }">
                      {{ __('Summary') }}
                    </a>
                  </li>
                  @if ($request->status === 'COMPLETED' && !$request->errors)
                    @can('editData', $request)
                      <li class="nav-item">
                        <a id="editdata-tab" data-toggle="tab" href="#editdata" role="tab" aria-controls="editdata"
                          aria-selected="false" class="nav-link" @click="switchTab('editdata')">
                          {{ __('Data') }}
                        </a>
                      </li>
                    @endcan
                  @endif
                  <li class="nav-item">
                    <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab"
                      aria-controls="completed" aria-selected="false"
                      @click="switchTab('completed')">{{ __('Completed') }}</a>
                  </li>
                  @if (count($files) > 0 && !hasPackage('package-files'))
                    <li class="nav-item">
                      <a class="nav-link" id="files-tab" data-toggle="tab" href="#files" role="tab" aria-controls="files"
                        aria-selected="false" @click="switchTab('files')">{{ __('Files') }}</a>
                    </li>
                  @endif

                  <template v-for="{ tab } in packages">
                    <li class="nav-item">
                      <a class="nav-link" :id="tab.id" data-toggle="tab" :href="'#' + tab.target" role="tab"
                        @click="switchTab(tab.target)">
                        @{{ tab.name }}
                      </a>
                    </li>
                  </template>

                  <li class="nav-item" v-show="canViewPrint">
                    <a class="nav-link" id="forms-tab" data-toggle="tab" href="#forms" role="tab" aria-controls="forms"
                      aria-selected="false" @click="switchTab('forms')">
                      {{ __('Forms') }}
                    </a>
                  </li>
                  @isset($addons)
                    @foreach ($addons as $addon) @if (!empty($addon['title']))
                      <li class="nav-item">
                        <a class="nav-link" id="{{ $addon['id'] . '-tab' }}" data-toggle="tab" href="{{ '#' . $addon['id'] }}"
                          role="tab" aria-controls="{{ $addon['id'] }}" aria-selected="false">
                          {{ __($addon['title']) }}
                        </a>
                      </li>
                    @endif @endforeach
                  @endisset
                </template>
              </ul>
              <div class="tab-content" id="requestTabContent">
                <div class="tab-pane card card-body border-top-0 p-0" :class="{ active: activeErrors }" id="errors"
                  role="tabpanel" aria-labelledby="errors-tab">
                  <request-errors :errors="errorLogs"></request-errors>
                </div>
                <div class="tab-pane show card card-body border-top-0 p-0" :class="{ active: activePending }"
                  id="pending" role="tabpanel" aria-labelledby="pending-tab">
                  <request-detail ref="pending" :process-request-id="requestId" status="ACTIVE"
                    :is-process-manager="{{ $isProcessManager ? 'true' : 'false' }}"
                    :is-admin="{{ Auth::user()->is_administrator ? 'true' : 'false' }}">
                  </request-detail>
                </div>
                <div class="card card-body border-top-0 p-0"
                  v-bind:class="{ 'tab-pane':true, active: showSummary && !activePending}" id="summary" role="tabpanel"
                  aria-labelledby="summary-tab">
                  <template v-if="showSummary">
                    <template v-if="showScreenSummary">
                      <div class="p-3">
                        <vue-form-renderer ref="screen" :config="screenSummary.config" v-model="dataSummary"
                          :custom-css="screenSummary?.custom_css"
                          :computed="screenSummary.computed" />
                      </div>
                    </template>
                    <template v-if="showScreenRequestDetail && !showScreenSummary">
                      <div class="card">
                        <div class="card-body">
                          <vue-form-renderer ref="screenRequestDetail" :config="screenRequestDetail.config"
                            :custom-css="screenRequestDetail.custom_css"
                            v-model="dataSummary" />
                        </div>
                      </div>
                    </template>
                    <template v-if="!showScreenSummary && !showScreenRequestDetail">
                      <template v-if="summary.length > 0">
                        <template v-if="!activePending">
                          <div class="card border-0">
                            <data-summary :summary="dataSummary"></data-summary>
                          </div>
                        </template>
                        <template v-else>
                          <div class="card border-0">
                            <div class="card-header bg-white">
                              <h5 class="m-0">
                                {{ __('Request In Progress') }}
                              </h5>
                            </div>

                            <div class="card-body">
                              <p class="card-text">
                                {{ __('This Request is currently in progress.') }}
                                {{ __('This screen will be populated once the Request is completed.') }}
                              </p>
                            </div>
                          </div>
                        </template>
                      </template>
                      <template v-else>
                        <div class="card border-0">
                          <div class="card-header bg-white">
                            <h5 class="m-0">
                              {{ __('No Data Found') }}
                            </h5>
                          </div>

                          <div class="card-body">
                            <p class="card-text">
                              {{ __("Sorry, this request doesn't contain any information.") }}
                            </p>
                          </div>
                        </div>
                      </template>

                    </template>
                  </template>
                </div>
                @if ($request->status === 'COMPLETED')
                  @can('editData', $request)
                    <div id="editdata" role="tabpanel" aria-labelledby="editdata"
                      class="tab-pane card card-body border-top-0 p-3">
                      @include('tasks.editdata')
                    </div>
                  @endcan
                @endif
                <div class="tab-pane card card-body border-top-0 p-0" id="completed" role="tabpanel"
                  aria-labelledby="completed-tab">
                  <request-detail ref="completed" :process-request-id="requestId" status="CLOSED"
                    :is-admin="{{ Auth::user()->is_administrator ? 'true' : 'false' }}">
                  </request-detail>
                </div>

                <template v-for="{ tab, component } in packages">
                  <div class="tab-pane card card-body border-top-0 p-0" :id="tab.target" role="tabpanel">
                    <component :is="component" :process-request-id="requestId"></component>
                  </div>
                </template>

                <div class="tab-pane card card-body border-top-0 p-3" id="files" role="tabpanel"
                  aria-labelledby="files-tab">
                  <div class="card">
                    <div>
                      <table class="vuetable table-hover table">
                        <thead>
                          <tr>
                            <th>{{ __('File Name') }}</th>
                            <th>{{ __('MIME Type') }}</th>
                            <th>{{ __('Created At') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($files as $file)
                            <tr>
                              <td>
                                <a
                                  href="{{ url('request/' . $request->id . '/files/' . $file->id) }}">{{ $file->file_name }}</a>
                              </td>
                              <td>{{ $file->mime_type }}</td>
                              <td>{{ $file->created_at->format('m/d/y h:i a') }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="tab-pane card card-body border-top-0 p-0" id="forms" role="tabpanel"
                  aria-labelledby="forms-tab" v-show="canViewPrint">
                  <request-screens :id="requestId" :information="dataSummary" ref="forms">
                  </request-screens>
                </div>
                <div v-if="activeTab === 'overview'" class="tab-pane p-0" id="overview" role="tabpanel"
                  aria-labelledby="overview-tab">
                  <div class="card card-height" style="border-top: none !important;">
                    <div class="card-body">
                      <new-overview />
                    </div>
                  </div>
                </div>

                @isset($addons)
                  @foreach ($addons as $addon)
                    <div class="tab-pane show" id="{{ $addon['id'] }}" role="tabpanel"
                      aria-labelledby="{{ $addon['id'] }}">
                      {!! $addon['content'] !!}
                    </div>
                  @endforeach
                @endisset
              </div>
            </div>
              <timeline commentable_id="{{ $request->getKey() }}" commentable_type="{{ get_class($request) }}"
                :adding="false" :readonly="request.status === 'COMPLETED'"
                :timeline="false" />
          </div>

          @if (shouldShow('requestStatusContainer'))
            <div class="slide-control">
              <a href="#" @click="hideMenu">
                <i class="fa" :class="{ 'fa-caret-left' : !showMenu, 'fa-caret-right' : showMenu }"></i>
              </a>
            </div>
            <div class="menu">
              <template v-if="statusLabel">
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
                      @click="switchTabInfo('details')"
                    >
                      @{{ __('Details') }}
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
                      @click="switchTabInfo('comments')"
                    >
                      @{{ __('Comments') }}
                    </button>
                  </li>
                </ul>
              </template>
              <div class="menu-tab-content">
                <div id="collapse-info" class="collapse show width">
                  <div
                    v-if="showInfo"
                    id="details"
                    v-bind:class="{ 'tab-pane':true, show: showInfo, active: showInfo }"
                    role="tabpanel"
                    aria-labelledby="details-tab"
                  >
                    <div class="ml-md-3 mt-md-0 mt-3">
                      <template v-if="statusLabel">
                        <div class="card collapse-content">
                          <ul class="list-group list-group-flush w-100">
                            @if ($canCancel == true && $request->status === 'ACTIVE')
                              <li class="list-group-item">
                                <button type="button" class="btn btn-outline-custom btn-block" @click="onCancel"
                                  data-test="cancel-request"
                                  aria-haspopup="dialog">
                                  {{ __('Cancel Request') }}
                                </button>
                              </li>
                            @endif
                            <li class="list-group-item">
                              <button type="button" class="btn btn-outline-custom btn-block" @click="onReturnCase"
                                data-test="request-go-to-case"
                                aria-haspopup="dialog">
                                <i class="fa fa-arrow-up"></i>
                                {{ __('Go to Case') }}
                              </button>
                            </li>
                            <div :class="classStatusCard">
                              <span style="margin:0; padding:0; line-height:1">@{{ __(statusLabel) }}</span>
                            </div>
                            @if ($request->participants->count())
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Participants') }}:</p>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                  :input-data="participants" hide-name="true"></avatar-image>
                              </li>
                            @endif
                            <li class="list-group-item">
                              <p class="section-title">@{{ __(labelDate) }}:</p>
                              <i class="far fa-calendar-alt"></i>
                              <small>@{{ moment(statusDate).format() }}</small>
                            </li>
                            <li class="list-group-item">
                              <p class="section-title">{{ __('Process') }}</p>
                              {{ $request->name }}
                              <!-- This is the name defined in the installation of connector-docusign 'DocuSignAuthentication' -->
                              @if ($request->process->name !== 'DocuSignAuthentication')
                                <p class="launchpad-link">
                                  <a href="{{route('process.browser.index', [$request->process_id])}}">
                                    {{ __('Open Process Launchpad') }}
                                  </a>
                                </p>
                              @endif
                            </li>
                            @if ($request->user_id)
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Requested By') }}:</p>
                                <avatar-image
                                  v-if="userRequested"
                                  size="32"
                                  class="d-inline-flex pull-left align-items-center"
                                  :input-data="requestBy"
                                  display-name="true"
                                ></avatar-image>
                                <span v-if="!userRequested">{{ __('Web Entry') }}</span>
                              </li>
                            @endif
                            @if ($canManuallyComplete == true)
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Manually Complete Request') }}</p>
                                <button type="button" class="btn btn-outline-success btn-block" data-toggle="modal"
                                  @click="completeRequest">
                                  <i class="fas fa-stop-circle"></i> {{ __('Complete') }}
                                </button>
                              </li>
                            @endif
                            @if ($canRetry === true)
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Retry Request') }}</p>
                                <button id="retryRequestButton" type="button" class="btn btn-outline-info btn-block"
                                  data-toggle="modal" :disabled="retryDisabled" @click="retryRequest">
                                  <i class="fas fa-sync"></i> {{ __('Retry') }}
                                </button>
                              </li>
                            @endif
                            @if ($eligibleRollbackTask)
                              @can('rollback', $errorTask)
                                <li
                                  v-if="{{ $isProcessManager ? 'true' : 'false' }} ||
                                    {{ Auth::user()->is_administrator ? 'true' : 'false' }}"
                                  class="list-group-item"
                                >
                                  <p class="section-title">{{ __('Rollback Request') }}</p>
                                  <button
                                    id="retryRequestButton"
                                    type="button"
                                    class="btn btn-outline-info btn-block"
                                    data-toggle="modal"
                                    @click="rollback({{ $errorTask->id }}, '{{ $eligibleRollbackTask->element_name }}')"
                                  >
                                    <i class="fas fa-undo"></i> {{ __('Rollback') }}
                                  </button>
                                  <small>{{ __('Rollback to task') }}: <b>{{ $eligibleRollbackTask->element_name }}</b> ({{ $eligibleRollbackTask->element_id }})</small>
                                </li>
                              @endcan
                            @endif
                            @if ($request->parentRequest)
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Parent Request') }}</p>
                                <i :class="requestStatusClass('{{ $request->parentRequest->status }}')"></i>
                                <a href="/requests/{{ $request->parentRequest->getKey() }}">{{ $request->parentRequest->name }}</a>
                              </li>
                            @endif
                            @if (count($request->childRequests))
                              <li class="list-group-item">
                                <p class="section-title">{{ __('Child Requests') }}</p>
                                @foreach ($request->childRequests as $childRequest)
                                  <div>
                                    <i :class="requestStatusClass('{{ $childRequest->status }}')"></i>
                                    <a href="/requests/{{ $childRequest->getKey() }}">{{ $childRequest->name }}</a>
                                  </div>
                                @endforeach
                              </li>
                            @endif
                          </ul>
                        </div>
                      </template>
                    </div>
                  </div>
                  <div
                    v-if="!showInfo"
                    id="comments"
                    v-bind:class="{ 'tab-pane':true, show: !showInfo, active: !showInfo }"
                    role="tabpanel"
                    aria-labelledby="comments-tab"
                  >
                    <div class="ml-md-3 mt-md-0 mt-3 collapse-content">
                      <template v-if="panCommentInVueOptionsComponents">
                        <comment-container
                          :commentable_id="request.id"
                          commentable_type="{{ get_class($request) }}"
                          name="{{ $request->name }}"
                          :readonly="request.status === 'COMPLETED'"
                          :header="false"
                          :case_number="{{ json_encode($request->case_number ?? null) }}"
                        />
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@section('js')
  <script src="{{ mix('js/processes/modeler/initialLoad.js') }}"></script>
  <script>
    window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    window.Processmaker = {
      csrfToken: "{{csrf_token()}}",
      userId: "{{\Auth::user()->id}}",
      messages: [],
      apiTimeout: {{config('app.api_timeout')}}
    };
  </script>

  @foreach($managerModelerScripts as $script)
    <script src="{{ $script }}"></script>
  @endforeach
  @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
  @if (hasPackage('package-files'))
    <!-- TODO: Replace with script injector like we do for modeler and screen builder -->
    <script src="{{ mix('js/manager.js', 'vendor/processmaker/packages/package-files') }}"></script>
  @endif

  <script>
    window.PM4ConfigOverrides = {
      requestFiles: @json($request->requestFiles())
    };
    const request = @json($request);
  </script>

  <script src="{{ mix('js/requests/show.js') }}"></script>
  <script>
    new Vue({
      el: '#request',
      mixins: addons,
      data() {
        return {
          activeTab: 'pending',
          showCancelRequest: false,
          //Edit data
          fieldsToUpdate: [],
          jsonData: '',
          selectedData: '',
          monacoLargeOptions: {
            automaticLayout: true,
          },
          showJSONEditor: false,
          data: @json($request->getRequestData()),
          requestId: @json($request->getKey()),
          request: @json($request->getRequestAsArray()),
          files: @json($files),
          refreshTasks: 0,
          canCancel: @json($canCancel),
          canViewPrint: @json($canPrintScreens),
          status: 'ACTIVE',
          userRequested: [],
          errorLogs: @json(['data' => $request->getErrors()]),
          disabled: false,
          retryDisabled: false,
          packages: [],
          processId: @json($request->process->id),
          canViewComments: @json($canViewComments),
          isObjectLoading: true,
          showTree: false,
          showInfo: true,
          showMenu: true,
          userConfiguration: @json($userConfiguration),
          urlConfiguration:'users/configuration',
        };
      },
      computed: {
        activeErrors() {
          return this.request.status === 'ERROR';
        },
        activePending() {
          return this.request.status === 'ACTIVE';
        },
        /**
         * Get the list of participants in the request.
         *
         */
        participants() {
          return this.request.participants;
        },
        /**
         * Request Summary - that is blank place holder if there are in progress tasks,
         * if the request is completed it will show key value pairs.
         *
         */
        showSummary() {
          return this.request.status === 'ACTIVE' || this.request.status === 'COMPLETED' || this.request.status ===
            'CANCELED';
        },
        /**
         * Show tasks if request status is not completed or pending
         *
         */
        showTasks() {
          return this.request.status !== 'COMPLETED' && this.request.status !== 'PENDING';
        },
        /**
         * If the screen summary is configured.
         **/
        showScreenSummary() {
          return this.request.summary_screen !== null;
        },
        /**
         * Get the summary of the Request.
         *
         */
        summary() {
          return this.request.summary;
        },
        /**
         * Get Screen summary
         * */
        screenSummary() {
          return this.request.summary_screen;
        },
        /**
         * prepare data screen
         **/
        dataSummary() {
          let options = {};
          this.request.summary.forEach(option => {
            if (option.type === 'datetime') {
              options[option.key] = moment(option.value).
                      tz(window.ProcessMaker.user.timezone).
                      format("MM/DD/YYYY HH:mm");
            } else if (option.type === 'date') {
              options[option.key] = moment(option.value).
                  tz(window.ProcessMaker.user.timezone).
                  format("MM/DD/YYYY");
            } else {
              options[option.key] = option.value;
            }
          });
          return options;
        },
        /**
         * If the screen request detail is configured.
         **/
        showScreenRequestDetail() {
          return !!this.request.request_detail_screen;
        },
        /**
         * Get Screen request detail
         * */
        screenRequestDetail() {
          if (!this.request.request_detail_screen) {
            return {
              config: null,
              custom_css: null,
            };
          }

          return {
            config: this.request.request_detail_screen.config,
            custom_css: this.request.request_detail_screen.custom_css
          };
        },
        classStatusCard() {
          let header = {
            'ACTIVE': 'active-style',
            'COMPLETED': 'active-style',
            'CANCELED': 'canceled-style ',
            'ERROR': 'canceled-style',
          };
          return 'card-header text-status ' + header[this.request.status.toUpperCase()];
        },
        labelDate() {
          let label = {
            'ACTIVE': 'In Progress Since',
            'COMPLETED': 'Completed On',
            'CANCELED': 'Canceled ',
            'ERROR': 'Failed On',
          };
          return label[this.request.status.toUpperCase()];
        },
        statusDate() {
          let status = {
            'ACTIVE': this.request.created_at,
            'COMPLETED': this.request.completed_at,
            'CANCELED': this.request.updated_at,
            'ERROR': this.request.updated_at,
          };

          return status[this.request.status.toUpperCase()];
        },
        statusLabel() {
          let status = {
            'ACTIVE': "{{ __('In Progress') }}",
            'COMPLETED': "{{ __('Completed') }}",
            'CANCELED': "{{ __('Canceled') }}",
            'ERROR': "{{ __('Error') }}",
          };

          return status[this.request.status.toUpperCase()];
        },
        requestBy() {
          return [this.request.user];
        },
        panCommentInVueOptionsComponents() {
            return 'pan-comment' in Vue.options.components;
        },
      },
      methods: {
        defineUserConfiguration() {
          this.userConfiguration = JSON.parse(this.userConfiguration.ui_configuration);
          this.showMenu = this.userConfiguration.requests.isMenuCollapse;
        },
        hideMenu() {
          this.showMenu = !this.showMenu;
          this.$root.$emit("sizeChanged", !this.showMenu);
          this.updateUserConfiguration();
        },
        updateUserConfiguration() {
          this.userConfiguration.requests.isMenuCollapse = this.showMenu;
          ProcessMaker.apiClient
            .put(
              this.urlConfiguration,
              {
                ui_configuration: this.userConfiguration,
              }
            )
            .catch((error) => {
              console.error("Error", error);
            });
        },
        switchTab(tab) {
          this.activeTab = tab;
          if (tab === 'overview') {
            this.isObjectLoading = true;
          }
          ProcessMaker.EventBus.$emit('tab-switched', tab);
        },
        switchTabInfo(tab) {
          this.showInfo = !this.showInfo;
          if (window.Intercom) {
            window.Intercom('update', { "hide_default_launcher": tab === 'comments' });
          }
        },
        onLoadedObject() {
          this.isObjectLoading = false;
        },
        requestStatusClass(status) {
          status = status.toLowerCase();
          let bubbleColor = {
            active: 'text-success',
            inactive: 'text-danger',
            error: 'text-danger',
            draft: 'text-warning',
            archived: 'text-info',
            completed: 'text-primary',
          };
          return 'fas fa-circle ' + bubbleColor[status] + ' small';
        },
        // Data editor
        updateRequestData() {
          const data = JSON.parse(this.jsonData);
          ProcessMaker.apiClient.put('requests/' + this.requestId, {
            data: data,
          }).then(response => {
            this.fieldsToUpdate.splice(0);
            ProcessMaker.alert(this.$t('The request data was saved.'), 'success');
          });
        },
        saveJsonData() {
          try {
            const value = JSON.parse(this.jsonData);
            this.updateRequestData();
          } catch (e) {
            // Invalid data
          }
        },
        editJsonData() {
          this.jsonData = JSON.stringify(this.data, null, 4);
        },
        /**
         * Refresh the Request details.
         *
         */
        refreshRequest() {
          this.$refs.pending.fetch();
          this.$refs.completed.fetch();
          ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
            params: {
              include: 'participants,user,summary,summaryScreen',
            },
          }).then((response) => {
            for (let attribute in response.data) {
              this.updateModel(this.request, attribute, response.data[attribute]);
            }
            this.refreshTasks++;
          });
        },
        /**
         * Update a model property.
         *
         */
        updateModel(obj, prop, value, defaultValue) {
          const descriptor = Object.getOwnPropertyDescriptor(obj, prop);
          value = value !== undefined ? value : (descriptor ? obj[prop] : defaultValue);
          if (descriptor && !(descriptor.get instanceof Function)) {
            delete obj[prop];
            Vue.set(obj, prop, value);
          } else if (descriptor && obj[prop] !== value) {
            Vue.set(obj, prop, value);
          }
        },
        /**
         * Listen for Request updates.
         *
         */
        listenRequestUpdates() {
          let userId = document.head.querySelector('meta[name="user-id"]').content;
          Echo.private(`ProcessMaker.Models.User.${userId}`).notification((token) => {
            if (token.request_id === this.requestId) {
              this.refreshRequest();
            }
          });
        },
        /**
         * disable buttons in screen
         */
        cleanScreenButtons() {
          if (this.showScreenSummary) {
            this.$refs.screen.config[0].items.forEach(item => {
              item.config.disabled = true;
              if (item.component === 'FormButton') {
                item.config.event = '';
                item.config.variant = item.config.variant + '  disabled';
              }
            });
          }
        },
        okCancel() {
          //single click
          if (this.disabled) {
            return;
          }
          this.disabled = true;
          ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
            status: 'CANCELED',
          }).then(response => {
            ProcessMaker.alert(this.$t('The request was canceled.'), 'success');
            window.location.reload();
          }).catch(error => {
            this.disabled = false;
          });
        },
        onCancel() {
          ProcessMaker.confirmModal(
            this.$t('Caution!'),
            this.$t('Are you sure you want cancel this request?'),
            '',
            () => {
              this.okCancel();
            },
          );
        },
        onReturnCase() {
          window.location.replace(`/cases/${request.case_number}`);
        },
        completeRequest() {
          ProcessMaker.confirmModal(
            this.$t('Caution!'),
            this.$t('Are you sure you want to complete this request?'),
            '',
            () => {
              ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
                status: 'COMPLETED',
              }).then(() => {
                ProcessMaker.alert(this.$t('Request Completed'), 'success');
                location.reload();
              });
            });
        },
        retryRequest() {
          const apiRequest = () => {
            this.retryDisabled = true
            let success = true

            ProcessMaker.apiClient.put(`requests/${this.requestId}/retry`).then(response => {
              if (response.status !== 200) {
                return;
              }

              const message = response.data.message;
              success = response.data.success || false

              if (success) {
                if (Array.isArray(message)) {
                  for (let line of message) {
                    ProcessMaker.alert(this.$t(line), 'success')
                  }
                }
              } else {
                ProcessMaker.alert(this.$t("Request could not be retried"), 'danger')

              }
            }).finally(() => setTimeout(() => location.reload(), success ? 3000 : 1000))
          }

          ProcessMaker.confirmModal(
            this.$t('Confirm'),
            this.$t('Are you sure you want to retry this request?'),
            'default',
            apiRequest
          );
        },
        rollback(errorTaskId, rollbackToName) {
          ProcessMaker.confirmModal(
            this.$t('Confirm'),
            this.$t('Are you sure you want to rollback to the task @{{name}}? Warning! This request will continue as the current published process version.', { name: rollbackToName }),
            'default',
            () => {
              ProcessMaker.apiClient.post(`tasks/${errorTaskId}/rollback`).then(response => {
                location.reload();
              });
            }
          )
        },
      },
      mounted() {
        this.packages = window.ProcessMaker.requestShowPackages;
        this.listenRequestUpdates();
        this.cleanScreenButtons();
        this.editJsonData();
        this.defineUserConfiguration();
      },
    });
  </script>
  <script>
    const inflightData = @json($inflightData);
    window.ProcessMaker.modeler = {
      xml: @json($bpmn),
      configurables: [],
      requestCompletedNodes: inflightData.requestCompletedNodes,
      requestInProgressNodes: inflightData.requestInProgressNodes,
      requestIdleNodes: inflightData.requestIdleNodes,
      requestId: inflightData.requestId,
    }
  
    window.ProcessMaker.EventBus.$on('modeler-start', async ({
      loadXML
    }) => {
      loadXML(window.ProcessMaker.modeler.xml);
    });
    window.ProcessMaker.PMBlockList = @json($pmBlockList);
  </script>
@endsection

@section('css')
<link href="{{ mix('css/collapseDetails.css') }}" rel="stylesheet">
<style>
  .hidden {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
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
  .active-style {
    background-color: #4ea075;
  }
  .canceled-style {
    background-color: #ed4858;
  }
  .card-header:first-child.text-status {
    border-radius: 6px;
  }
  .launchpad-link {
    margin-top: 5px;
  }
  .menu-tab-content {
    margin-left: -16px;
  }
  .main-paper {
    position: static !important;
  }
  .card-height {
    height: 50vh;
  }
</style>
@endsection
