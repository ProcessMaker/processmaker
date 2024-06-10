<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval';
        object-src 'self';
        worker-src 'self' blob:;">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">
    <meta name="open-ai-nlq-to-pmql" content="{{ config('app.open_ai_nlq_to_pmql') }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    <meta name="screen-cache-enabled" content="{{ config('app.screen.cache_enabled') ? 'true' : 'false' }}">
    <meta name="screen-cache-timeout" content="{{ config('app.screen.cache_timeout') }}">
    @if(Auth::user())
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <meta name="datetime-format" content="{{ Auth::user()->datetime_format ?: config('app.dateformat') }}">
    <meta name="timezone" content="{{ Auth::user()->timezone ?: config('app.timezone') }}">
    <meta name="request-id" content="{{ $task->processRequest->id }}">
    @endif
    <meta name="timeout-worker" content="{{ mix('js/timeout.js') }}">
    <meta name="timeout-length" content="{{ Session::has('rememberme') && Session::get('rememberme') ? "Number.MAX_SAFE_INTEGER" : config('session.lifetime') }}">
    <meta name="timeout-warn-seconds" content="{{ config('session.expire_warning') }}">
    @if(Session::has('_alert'))
      <meta name="alert" content="show">
      @php
      list($type,$message) = json_decode(Session::get('_alert'));
      Session::forget('_alert');
      @endphp
      <meta name="alertVariant" content="{{$type}}">
      <meta name="alertMessage" content="{{$message}}">
    @endif

    <title>{{__('Edit Task')}}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="/css/bpmn-symbols/css/bpmn.css" rel="stylesheet">
    @yield('css')
    <script type="text/javascript">
    @if(Auth::user())
      window.Processmaker = {
        csrfToken: "{{csrf_token()}}",
        userId: "{{\Auth::user()->id}}",
        messages: [],
        apiTimeout: {{config('app.api_timeout')}}
      };
      @if(config('broadcasting.default') == 'redis')
        window.Processmaker.broadcasting = {
          broadcaster: "socket.io",
          host: "{{config('broadcasting.connections.redis.host')}}",
          key: "{{config('broadcasting.connections.redis.key')}}"
        };
      @endif
      @if(config('broadcasting.default') == 'pusher')
        window.Processmaker.broadcasting = {
          broadcaster: "pusher",
          key: "{{config('broadcasting.connections.pusher.key')}}",
          cluster: "{{config('broadcasting.connections.pusher.options.cluster')}}",
          forceTLS: {{config('broadcasting.connections.pusher.options.use_tls') ? 'true' : 'false'}},
          debug: {{config('broadcasting.connections.pusher.options.debug') ? 'true' : 'false'}}
        };
      @endif
    @endif
  </script>
    @isset($addons)
        <script>
            var addons = [];
        </script>
        @foreach ($addons as $addon)
            @if (!empty($addon['script']))
                {!! $addon['script'] !!}
            @endif
        @endforeach
    @endisset

    @if (config('global_header'))
        <!-- Start Global Header -->
        {!! config('global_header') !!}
        <!-- End Global Header -->
    @endif
</head>
<body>
  <div id="sidebar" style="display: 'none'"></div>
  <div id="navbar" style="display: 'none'"></div>
    <div v-cloak id="task" class="container-fluid px-3">
        <div class="d-flex flex-column flex-md-row" id="interactionListener">
            <div class="flex-grow-1">
                <div v-if="isSelfService" class="alert alert-primary" role="alert">
                    <button type="button" class="btn btn-primary" @click="claimTask">{{__('Claim Task')}}</button>
                    {{__('This task is unassigned, click Claim Task to assign yourself.')}}
                </div>
                <div class="container-fluid h-100 d-flex flex-column">
                    <div id="tabContent" class="tab-content flex-grow-1">
                        <task
                          ref="task"
                          class="card border-0"
                          v-model="formData"
                          :initial-task-id="{{ $task->id }}"
                          :initial-request-id="{{ $task->process_request_id }}"
                          :user-id="{{ Auth::user()->id }}"
                          csrf-token="{{ csrf_token() }}"
                          initial-loop-context="{{ $task->getLoopContext() }}"
                          @task-updated="taskUpdated"
                          @after-submit="afterSubmit"
                          @submit="submit"
                          @completed="completed"
                          @@error="error"
                          @closed="closed"
                          @redirect="redirectToTask"
                          :task-preview="true"
                          :always-allow-editing="alwaysAllowEditing"
                          :disable-interstitial="disableInterstitial"
                        ></task>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<!-- Scripts -->
@if(config('broadcasting.default') == 'redis')
<script src="{{config('broadcasting.connections.redis.host')}}/socket.io/socket.io.js"></script>
@endif
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script>
  window.ProcessMaker.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
</script>
<script src="{{ mix('js/app-layout.js') }}"></script>
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

    const task = @json($task);
    const userHasAccessToTask = {{ Auth::user()->can('update', $task) ? "true": "false" }};
    const userIsAdmin = {{ Auth::user()->is_administrator ? "true": "false" }};
    const userIsProcessManager = {{ Auth::user()->id === $task->process?->manager_id ? "true": "false" }};
    const screenFields = @json($screenFields);

  </script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/tasks/show.js')}}"></script>
    <script>
      const store = new Vuex.Store();
      const main = new Vue({
        mixins:addons,
        store: store,
        el: "#task",
        data: {
          //Edit data
          fieldsToUpdate: [],
          jsonData: "",
          monacoLargeOptions: {
            automaticLayout: true,
          },
          showJSONEditor: false,
          windowParent: window.parent.ProcessMaker,
          // Reassignment
          selected: null,
          selectedIndex: -1,
          usersList: [],
          filter: "",
          showReassignment: false,
          task,
          userHasAccessToTask,
          statusCard: "card-header text-capitalize text-white bg-success",
          selectedUser: [],
          hasErrors: false,
          redirectInProcess: false,
          formData: {},
          submitting: false,
          userIsAdmin,
          userIsProcessManager,
          is_loading: false,
          autoSaveDelay: 5000,
          userHasInteracted: false,
          initialFormDataSet: false,
          alwaysAllowEditing: window.location.search.includes('alwaysAllowEditing=1'),
          disableInterstitial: window.location.search.includes('disableInterstitial=1'),
          validateForm: true
        },
        watch: {
          task: {
            deep: true,
            handler(task, oldTask) {
              window.ProcessMaker.breadcrumbs.taskTitle = task.element_name;
              if (task && oldTask && task.id !== oldTask.id) {
                history.replaceState(null, null, `/tasks/${task.id}/edit/preview`);
              }
            }
          },
          screenFilteredData: {
            deep: true,
            handler() {
              this.sendEvent('dataUpdated', this.screenFilteredData);
            }
          },
        },
        computed: {
          screenFilteredData () {
            return this.filterScreenFields(this.formData);
          },
          taskDefinitionConfig () {
            let config = {};
            if (this.task.definition && this.task.definition.config) {
              return JSON.parse(this.task.definition.config);
            }
            return {};
          },
          dueLabel() {
            const dueLabels = {
              'open': 'Due',
              'completed': 'Completed',
              'overdue': 'Due',
            };
            return dueLabels[this.task.advanceStatus] || '';
          },
          isSelfService() {
            return this.task.process_request.status === 'ACTIVE' && this.task.is_self_service;
          },
          dateDueAt () {
            return this.task.due_at;
          },
          createdAt () {
            return this.task.created_at;
          },
          completedAt () {
            return this.task.completed_at;
          },
          showDueAtDates () {
            return this.task.status !== "CLOSED";
          },
          disabled () {
            return this.selectedUser ? this.selectedUser.length === 0 : true;
          },
          styleDataMonaco () {
            let height = window.innerHeight * 0.55;
            return "height: " + height + "px; border:1px solid gray;";
          }
        },
        methods: {
          afterSubmit(event) {
            event.validation = this.validateForm;
          },
          filterScreenFields(taskData) {
            const filteredData = {};
            screenFields.forEach(field => {
              _.set(filteredData, field, _.get(taskData, field, null));
            });
            return filteredData;
          },
          sendEvent(name, data) {
              const event = new CustomEvent(name, {
                detail: {
                  event_parent_id: Number(window.frameElement.getAttribute('event-parent-id')),
                  data: data
                },
              });
              window.parent.dispatchEvent(event);
          },
          sendUserHasInteracted() {
            if (!this.userHasInteracted) {
              this.userHasInteracted = true;
              this.sendEvent('userHasInteracted', true);
            }
          },
          completed(processRequestId) {
            // avoid redirection if using a customized renderer
            if(this.task.component && this.task.component === 'AdvancedScreenFrame') {
              return;
            }
            setTimeout(() => {
              parent.location.reload();
            }, 200);
          },
          error(processRequestId) {
            this.$refs.task.showSimpleErrorMessage();
          },
          redirectToTask(task, force = false) {
            this.redirect(`/tasks/${task}/edit/preview`, force);
          },
          closed(taskId) {
            // avoid redirection if using a customized renderer
            if (this.task.component && this.task.component === 'AdvancedScreenFrame') {
              return;
            }
            this.redirect("/tasks");
          },
          claimTask() {
            ProcessMaker.apiClient
              .put("tasks/" + this.task.id, {
                user_id: window.ProcessMaker.user.id,
                is_self_service: 0,
              })
              .then(response => {
                this.windowParent.alert(this.$t('The task was successfully claimed'), 'primary', 5, true);
                parent.location.reload();
              });
          },
          // Data editor
          updateRequestData () {
            const data = JSON.parse(this.jsonData);
            ProcessMaker.apiClient
              .put("requests/" + this.task.process_request_id, {
                data: data,
                task_element_id: this.task.element_id,
              })
              .then(response => {
                this.fieldsToUpdate.splice(0);
                this.windowParent.alert(this.$t('The request data was saved.'), "success");
              });
          },
          saveJsonData () {
            try {
              const value = JSON.parse(this.jsonData);
              this.updateRequestData();
            } catch (e) {
              // Invalid data
            }
          },
          editJsonData () {
            this.jsonData = JSON.stringify(this.task.request_data, null, 4);
          },
          // Reassign methods
          show () {
            this.showReassignment = true;
          },
          cancelReassign () {
            this.showReassignment = false;
            this.selectedUser = [];
          },
          reassignUser () {
            if (this.selectedUser) {
              ProcessMaker.apiClient
                .put("tasks/" + this.task.id, {
                  user_id: this.selectedUser.id
                })
                .then(response => {
                  this.showReassignment = false;
                  this.selectedUser = [];
                  this.redirect('/tasks');
                });
            }
          },
          redirect(to, forceRedirect = false) {
            if (this.redirectInProcess && !forceRedirect) {
              return;
            }
            this.redirectInProcess = true;
            window.location.href = to;
          },
          assignedUserAvatar (user) {
            return [{
              src: user.avatar,
              name: user.fullname
            }];
          },
          resizeMonaco () {
            let editor = this.$refs.monaco.getMonaco();
            editor.layout({height: window.innerHeight * 0.65});
          },
          prepareData() {
              this.updateRequestData = debounce(this.updateRequestData, 1000);
              this.editJsonData();
          },
          updateTask(val) {
            this.$set(this, 'task', val);
          },
          submit(task, loading, buttonInfo) {
            if (window.location.search.includes('dispatchSubmit=1')) {
              this.sendEvent('formSubmit', buttonInfo);

            } else if (this.isSelfService) {
              this.windowParent.alert(this.$t('Claim the Task to continue.'), 'warning');

            } else {
              if (this.submitting) {
                return;
              }

              let message = this.$t('Task Completed Successfully');
              const taskId = task.id;
              this.submitting = true;
              ProcessMaker.apiClient
              .put("tasks/" + taskId, {status:"COMPLETED", data: this.formData})
              .then(() => {
                this.windowParent.alert(message, 'success', 5, true);
              })
              .catch(error => {
                // If there are errors, the user will be redirected to the request page
                // to view error details. This is done in loadTask in Task.vue
                if (error.response?.status && error.response?.status === 422) {
                  // Validation error
                  Object.entries(error.response.data.errors).forEach(([key, value]) => {
                    this.windowParent.alert(`${key}: ${value[0]}`, 'danger', 0);
                  });
                }
              }).finally(() => {
                this.submitting = false;
                setTimeout(() => {
                  parent.location.reload();
                }, 200);
              })
            }

          },
          taskUpdated(task) {
            this.task = task;
            this.formData = _.cloneDeep(this.$refs.task.requestData);
            this.$nextTick(() => {
              this.sendEvent('taskReady', this.task?.id);
            });
          },
          validateBase64(field) {
            var regex = /^data:image\/\w+;base64,/;
            return regex.test(field);
          },
        },
        mounted() {
          this.prepareData();

          window.addEventListener('sendValidateForm', event => {
            this.validateForm = event.detail;
          });

          window.addEventListener('fillData', event => {
            const newData = {};
            screenFields.forEach((field) => {

              const existingValue = _.get(this.formData, field, null);

              if(this.validateBase64(existingValue)) {
                _.set(newData, field, existingValue);
                return;
              }
              let quickFillValue;

              if (existingValue) {
                // If the value exists in the task data, don't overwrite it
                quickFillValue = existingValue;
              } else {
                // use the value from the quick fill(event.detail)
                quickFillValue = _.get(event.detail, field, null);
              }
              // Set the value. This handles nested values using dot notation in 'field' string
              _.set(newData, field, quickFillValue);
            });

            this.formData = newData;
          });

          window.addEventListener('eraseData', event => {
            this.formData = {};
          });

          // listen for keydown on element with id interactionListener
          const interactionListener = document.getElementById('interactionListener');
          interactionListener.addEventListener('mousedown', (event) => {
            this.sendUserHasInteracted();
          });
          interactionListener.addEventListener('keydown', (event) => {
            this.sendUserHasInteracted();
          });
        }
      });
      window.ProcessMaker.breadcrumbs.taskTitle = @json($task->element_name)
    
    </script>


    <style>
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
    </style>
