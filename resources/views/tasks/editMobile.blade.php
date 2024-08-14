@extends('layouts.mobile')

@section('meta')
    <meta name="request-id" content="{{ $task->processRequest->id }}">
@endsection

@section('title')
  {{ __('Edit Task') }}
@endsection

@section('content_mobile')
<div v-cloak id="taskMobile">
  <navbar-task-mobile
    :task="task"
    :userIsAdmin="userIsAdmin"
    :userIsProcessManager="userIsProcessManager"
    @reload-task="handleReloadTask"
  >
  </navbar-task-mobile>
  
  <div class="d-flex flex-column">
    <div class="flex-fill">
      <div v-if="isSelfService" class="alert alert-primary" role="alert">
        <button type="button" class="btn btn-primary" @click="claimTask">{{__('Claim Task')}}</button>
        {{__('This task is unassigned, click Claim Task to assign yourself.')}}
      </div>
      <div id="interactionListener" class="container-fluid h-100 d-flex flex-column">
        <div id="tabContent" class="tab-content m-3 flex-grow-1">
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
            @submit="submit"
            @completed="completed"
            @@error="error"
            @closed="closed"
            @redirect="redirectToTask"
            @form-data-changed="handleFormDataChange"
          ></task>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showConfirmationMessage" class="confirmation-message">
    {{ __('Saved') }}
  </div>
  @isset($assignedToAddons)
    @foreach ($assignedToAddons as $addon)
      {!! $addon['content'] ?? '' !!}
    @endforeach
  @endisset
</div>
@endsection

@section('js')
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
    const userIsProcessManager = {{ Auth::user()->id === $task->process->manager_id ? "true": "false" }};
    var screenFields = @json($screenFields);
    window.ProcessMaker.taskDraftsEnabled = @json($taskDraftsEnabled);

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
        el: "#taskMobile",
        data: {
          //Edit data
          fieldsToUpdate: [],
          jsonData: "",
          monacoLargeOptions: {
            automaticLayout: true,
          },
          showJSONEditor: false,

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
          showTree: false,
          userHasInteracted: false,
          autoSaveDelay: 2000,
          showConfirmationMessage: false,
        },
        watch: {
          task: {
            deep: true,
            handler(task, oldTask) {
              window.ProcessMaker.breadcrumbs.taskTitle = task.element_name;
              if (task && oldTask && task.id !== oldTask.id) {
                history.replaceState(null, null, `/tasks/${task.id}/edit`);
              }
            }
          },
          formData: {
            deep: true,
            handler(formData) {
              if (this.userHasInteracted) {
                if (this.formDataWatcherActive)
                {
                  this.handleAutosave();
                  this.userHasInteracted = false;
                } else {
                  this.formDataWatcherActive = true;
                }
              }
            }
          },
        },
        computed: {
          taskDefinitionConfig () {
            let config = {};
            if (this.task.definition && this.task.definition.config) {
              return JSON.parse(this.task.definition.config);
            }
            return {};
          },
          taskHasComments() {
            const commentsPackage = 'comment-editor' in Vue.options.components;
            let config = {};
            if (commentsPackage && this.task.definition && this.task.definition.config) {
              config = JSON.parse(this.task.definition.config);
            }
            return config;
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
          completed(processRequestId) {
            // avoid redirection if using a customized renderer
            if(this.task.component && this.task.component === 'AdvancedScreenFrame') {
              return;
            }
            this.redirect(`/requests/${processRequestId}`);
          },
          error(processRequestId) {
            this.$refs.task.showSimpleErrorMessage();
          },
          redirectToTask(task, force = false) {
            this.redirect(`/tasks/${task}/edit`, force);
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
                window.location.reload();
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
                ProcessMaker.alert(this.$t('The request data was saved.'), "success");
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
          submit(task) {
            if (this.isSelfService) {
              ProcessMaker.alert(this.$t('Claim the Task to continue.'), 'warning');
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
                window.ProcessMaker.alert(message, 'success', 5, true);
              })
              .catch(error => {
                // If there are errors, the user will be redirected to the request page
                // to view error details. This is done in loadTask in Task.vue
                if (error.response?.status && error.response?.status === 422) {
                  // Validation error
                  Object.entries(error.response.data.errors).forEach(([key, value]) => {
                    window.ProcessMaker.alert(`${key}: ${value[0]}`, 'danger', 0);
                  });
                }
              }).finally(() => {
                this.submitting = false;
              })
            }

          },
          taskUpdated(task) {
            this.task = task;
          },
          autosaveApiCall() {
            if (!this.taskDraftsEnabled) {
              return;
            }
            const draftData = {};

            const saveDraft = () => {
              screenFields.forEach((field) => {
                _.set(draftData, field, _.get(this.formData, field));
              });

              return ProcessMaker.apiClient
              .put("drafts/" + this.task.id, draftData)
              .then((response) => {
                this.showConfirmationMessage = true;
                setTimeout(() => {
                    this.showConfirmationMessage = false;
                }, 3000);
                this.task.draft = _.merge(
                  {},
                  this.task.draft,
                  response.data
                );
              })
              .catch(() => {
                this.errorAutosave = true;
              })
            };
            if (screenFields.length === 0) {
              return this.updateScreenFields(this.task.id)
              .then(() => {
                return saveDraft();
              });
            } else {
              return saveDraft();
            }
          },
          updateScreenFields(taskId) {
            return ProcessMaker.apiClient
            .get(`tasks/${taskId}/screen_fields`)
            .then((response)=> {
              screenFields = response.data;
            });
          },
          sendUserHasInteracted() {
            if (!this.userHasInteracted) {
              this.userHasInteracted = true;
            }
          },
          handleFormDataChange() {
            if (this.userHasInteracted) {
              this.handleAutosave();
              this.userHasInteracted = false;
            }
          },
          handleReloadTask(value) {
            const taskComponent = this.$refs.task;
            taskComponent.loadTask();
          },
        },
        mounted() {
          this.prepareData();
          window.ProcessMaker.isSelfService = this.isSelfService;
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
    
@endsection

@section('css')
<style>
  .confirmation-message {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #4ea075;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
</style>
@endsection
