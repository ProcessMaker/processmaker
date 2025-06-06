<template>
  <div>
    <div v-if="tooltipButton === 'inboxRules' && showPreview">
      <splitpane-container  :size="50" :class-inbox="true">
        <div
        ref="tasks-preview"
        class="tasks-preview h-100 p-3"
        >
          <div class="d-flex w-100 mb-3">
             <slot name="header" v-bind:close="onClose" v-bind:screenFilteredTaskData="formData" v-bind:taskReady="taskReady"></slot>
          </div>
          <div :class="{
            'frame-container': tooltipButton === 'previewTask' || tooltipButton === '',
            'frame-container-full': tooltipButton === 'fullTask',
            'frame-container-inbox': tooltipButton === 'inboxRules'
            }"
            class="iframe-container">
            <iframe
              v-if="showFrame1"
              :title="$t('Preview')"
              ref="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              class="iframe"
              :src="linkTasks1"
              @load="frameLoaded('tasksFrame1')"
              :event-parent-id="_uid"
            />
            <iframe
              v-if="showFrame2"
              :title="$t('Preview')"
              ref="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
              class="iframe"
              :src="linkTasks2"
              @load="frameLoaded('tasksFrame2')"
              :event-parent-id="_uid"
            />

            <task-loading
              v-show="stopFrame"
              class="load-frame"
            />
          </div>
      </div>
          </splitpane-container>
    </div>

    <div v-else>
    <splitpane-container v-if="showPreview" :size="splitpaneSize">
      <div
        ref="tasks-preview"
        class="tasks-preview h-100 p-3 position-relative"
      >
          <div class="d-flex w-100 mb-3">
            <slot name="header" v-bind:close="onClose" v-bind:screenFilteredTaskData="formData" v-bind:taskReady="taskReady">
              <b-button-group>
                <b-button
                  class="arrow-button"
                  variant="outline-secondary"
                  :disabled="!existPrev || disableNavigation"
                  @click="goPrevNext('Prev')"
                >
                  <i class="fas fa-chevron-left" />
                </b-button>
                <b-button
                  class="arrow-button"
                  variant="outline-secondary"
                  :disabled="!existNext || disableNavigation"
                  @click="goPrevNext('Next')"
                >
                  <i class="fas fa-chevron-right" />
                </b-button>
              </b-button-group>
              <task-save-notification
                :options="options"
                :task="task"
                :date="lastAutosave"
                :error="errorAutosave"
                :form-data="formData"
                :size="headerResponsive()"
              />
              <div class="ml-auto mr-0 text-right">
                <ellipsis-menu
                  v-if="ellipsisButton"
                  :actions="actions"
                  :data="task"
                  :divider="false"
                  style="float:none; color: #566877;"
                  @navigate="onProcessNavigate"
                />
                <b-button-group
                  v-if="!ellipsisButton"
                  class="preview-group-button"
                >
                  <b-button
                    class="icon-button"
                    :aria-label="$t('Clear Draft')"
                    variant="light"
                    v-b-tooltip.hover 
                    :title="$t('Clear Draft')"
                    @click="eraseDraft()"
                    v-if="taskDraftsEnabled"
                  >
                    <img src="/img/smartinbox-images/eraser.svg" :alt="$t('No Image')">
                  </b-button>
                  <b-button
                    v-if="showQuickFillPreview === false"
                    class="icon-button"
                    :aria-label="$t('Quick fill')"
                    v-b-tooltip.hover 
                    :title="$t('Quick fill')"
                    variant="light"
                    @click="showQuickFillPreview = true"
                  >
                    <img
                      src="/img/smartinbox-images/fill.svg"
                      :alt="$t('No Image')"
                    >
                  </b-button>
                </b-button-group>
                <b-button-group
                  v-if="!ellipsisButton"
                  class="preview-group-button"
                >
                  <b-button
                    class="icon-button"
                    variant="light"
                    :aria-label="$t('Priority')"
                    v-b-tooltip.hover 
                    :title="$t('Priority')"
                    :class="{ 'button-priority': isPriority }"
                    @click="addPriority()"
                  >
                    <img
                      :src="
                        isPriority
                          ? '/img/priority.svg'
                          : '/img/priority-header.svg'
                      "
                      :alt="$t('No Image')"
                    >
                  </b-button>
                  <b-button
                    v-if="allowReassignment"
                    class="btn text-secondary icon-button"
                    variant="light"
                    :aria-label="$t('Reassign')"
                    v-b-tooltip.hover 
                    :title="$t('Reassign')"
                    @click="openReassignment()"
                  >
                    <i class="fas fa-user-friends" />
                  </b-button>
                  <b-button
                    class="btn text-secondary icon-button"
                    variant="light"
                    :aria-label="$t('Open Task')"
                    v-b-tooltip.hover 
                    :title="$t('Open Task')"
                    @click="openTask()"
                  >
                    <i class="fas fa-external-link-alt" />
                  </b-button>
                </b-button-group>

                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Close')"
                    v-b-tooltip.hover 
                    :title="$t('Close')"
                  @click="onClose();showReassignment=false;"
                >
                  <i class="fas fa-times" />
                </b-button>
              </div>
            </slot>
          </div>
          <div
            id="reassign-container"
            class="d-flex align-items-center overlay-div position-absolute top-0 start-0 w-100 bg-white shadow-lg p-2 pr-4"
            v-if="showReassignment"
          >
            <div class="mr-3">
              <label for="user">Assign to:</label>
            </div>
            <div class="flex-grow-1">
              <PMDropdownSuggest v-model="selectedUser"
                                 :options="reassignUsers"
                                 @onInput="onReassignInput"
                                 :placeholder="$t('Type here to search')">
                <template v-slot:pre-text="{ option }">
                  <b-badge variant="secondary" class="mr-2 custom-badges pl-2 pr-2 rounded-lg">{{ option.active_tasks_count }}</b-badge>
                </template>
              </PMDropdownSuggest>
            </div>
            <button type="button" class="btn btn-primary btn-sm ml-2" @click="reassignUser(false)" :disabled="disabled">
              {{ $t('Assign') }}
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm ml-2" @click="cancelReassign">
              {{ $t('Cancel') }}
            </button>
          </div>
          <div :class="{
            'frame-container': tooltipButton === 'previewTask' || tooltipButton === '',
            'frame-container-full': tooltipButton === 'fullTask',
            'frame-container-inbox': tooltipButton === 'inboxRules'
          }"
          class="iframe-container">
            <iframe
              v-if="showFrame1"
              :title="$t('Preview')"
              ref="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              class="iframe"
              :src="linkTasks1"
              :event-parent-id="_uid"
              @load="frameLoaded('tasksFrame1')"
            />
            <iframe
              v-if="showFrame2"
              :title="$t('Preview')"
              ref="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
              class="iframe"
              :src="linkTasks2"
              :event-parent-id="_uid"
              @load="frameLoaded('tasksFrame2')"
            />

            <task-loading
              v-show="stopFrame"
              class="load-frame"
            />
        </div>
        <splitpane-container v-if="showQuickFillPreview" :size="93">
          <quick-fill-preview
            class="quick-fill-preview"
            :task="task"
            :prop-from-button ="'previewTask'"
            :prop-columns="propColumns"
            :prop-filters="propFilters"
            @quick-fill-data="fillWithQuickFillData"
            @close="showQuickFillPreview = false"
          ></quick-fill-preview>
        </splitpane-container>
      </div>
    </splitpane-container>
    </div>
  </div>
</template>

<script>
import SplitpaneContainer from "./SplitpaneContainer.vue";
import TaskLoading from "./TaskLoading.vue";
import TaskSaveNotification from "./TaskSaveNotification.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import QuickFillPreview from "./QuickFillPreview.vue";
import PreviewMixin from "./PreviewMixin";
import autosaveMixins from "../../modules/autosave/autosaveMixin.js"
import PMDropdownSuggest from "../../components/PMDropdownSuggest.vue";
import reassignMixin from "../../common/reassignMixin";

export default {
  components: { SplitpaneContainer, TaskLoading, QuickFillPreview, TaskSaveNotification, EllipsisMenu, PMDropdownSuggest },
  mixins: [PreviewMixin, autosaveMixins, reassignMixin],
  props: ["tooltipButton", "propPreview"],
  data(){
    return {
    };
  },
  watch: {
    task: {
      deep: true,
      handler(task, previousTask) {
        if (task.draft) {
          this.lastAutosave = moment(task.draft.updated_at).format("DD MMMM YYYY | HH:mm");
        } else {
          this.lastAutosave = "-";
        }
        this.isPriority = task.is_priority;
        const priorityAction = this.actions.find(action => action.value === 'mark-priority');
        if (priorityAction) {
          priorityAction.content = this.isPriority ? 'Unmark Priority' : 'Mark as Priority';
        }
        if (this.task.id) {
          this.getTaskDefinitionForReassignmentPermission();
        }

        if (task?.id !== previousTask?.id) {
          this.userHasInteracted = false;
          this.setAllowReassignment();
        } 
      },
    },
    showPreview(value) {
      this.$emit("onWatchShowPreview", value);
    }
  },
  mounted() {
    if(this.propPreview){
      this.showPreview = true;
    }
    
    this.receiveEvent('taskReady', (taskId) => {
      this.taskReady = true;
    });

    this.receiveEvent("dataUpdated", (data) => {
      this.formData = data;
      if (this.userHasInteracted) {
        this.handleAutosave();
        this.disableNavigation = true;
      }
    });

    this.receiveEvent('userHasInteracted', () => {
      this.userHasInteracted = true;
    });

    this.$root.$on('pane-size', (value) => {
      this.size = value;
    });
    this.screenWidthPx = window.innerWidth;
    window.addEventListener('resize', this.updateScreenWidthPx);
    this.getUser();
    this.setAllowReassignment();
  },
  computed: {
    disabled() {
      return this.selectedUser ? false : true;
    },
  },
  methods: {
    fillWithQuickFillData(data) {
      const message = this.$t('Task Filled succesfully');
      this.sendEvent("fillData", data);
      this.showUseThisTask = false;
      ProcessMaker.alert(message, 'success');
      this.handleAutosave();
      this.disableNavigation = false;
    },
    sendEvent(name, data)
    {
      const event = new CustomEvent(name, {
        detail: data
      });
      if(this.showFrame1) {
        this.$refs["tasksFrame1"].contentWindow.dispatchEvent(event);
      }
      if(this.showFrame2) {
        this.$refs["tasksFrame2"].contentWindow.dispatchEvent(event);
      }
    },
    receiveEvent(name, callback) {
      window.addEventListener(name, (event) => {
        if (event.detail.event_parent_id !== this._uid) {
          return;
        }
        callback(event.detail.data);
      });
    },
    autosaveApiCall() {
      if (!this.taskDraftsEnabled) {
        return;
      }
      this.options.is_loading = true;
      const draftData = _.omitBy(this.formData, (value, key) => key.startsWith("_"));
      return ProcessMaker.apiClient
        .put("drafts/" + this.task.id, draftData)
        .then((response) => {
          this.task.draft = _.merge(
            {},
            this.task.draft,
            response.data
          );
        })
        .catch(() => {
          this.errorAutosave = true;
        })
        .finally(() => {
          this.options.is_loading = false;
          this.errorAutosave = false;
          this.disableNavigation = false;
        });
    },
    eraseDraft() {
      ProcessMaker.apiClient
        .delete("drafts/" + this.task.id)
        .then(response => {
          // No need to run resetRequestFiles here
          // because the iframe gets reloaded after
          // the draft is cleared
          this.isLoading = setTimeout(() => {
            this.stopFrame = true;
            this.taskTitle = this.$t("Task Lorem");
          }, 4900);
          this.showSideBar(this.task, this.data);
          this.task.draft = null;
          this.userHasInteracted = false;
        });
    },
    cancelReassign() {
      this.showReassignment = false;
      this.selectedUser = null;
    },
    openReassignment() {
      this.showReassignment = !this.showReassignment;
      this.getReassignUsers();
    },
    getTaskDefinitionForReassignmentPermission() {
      ProcessMaker.apiClient
        .get(`tasks/${this.task.id}?include=definition`)
        .then((response) => {
          this.taskDefinition = response.data;
        });
    },
    getUser() {
      ProcessMaker.apiClient
        .get(`users/${ProcessMaker.user.id}`)
        .then((response) => {
          this.user = response.data;
        });
    },
  }
};
</script>

<style>
.tasks-preview {
  box-sizing: border-box;
  display: block;
  overflow: hidden;
  position: relative;

}
.loadingFrame {
  opacity: 0.5;
}
.frame-container {
  display: grid;
}
.frame-container-full {
  display: grid;
  width: 93%
}
.frame-container-inbox {
  display: grid;
  width: 98%;
}
.load-frame {
  position: relative;
  display: block;
  width: 100%;
  padding: 0;
  overflow: auto;
  grid-row-start: 1;
  grid-column-start: 1;
}
.icon-button {
  display: inline-block;
  width: 46px;
  height: 36px;
  border: 1px solid #ccc;
  background-color: #fff;
  padding: 0px;
  border-radius: 5px;
  justify-content: center;
  align-items: center;
  vertical-align: unset;
}

.icon-button img {
  width: 16px;
  height: 16px;
}

.arrow-button {
  width: 46px;
  height: 36px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.arrow-button[disabled] {
  background-color: #ccc;
}

.button-container {
  display: flex;
  align-items: center;
}

.close-button {
  color: #888;
  padding: 0;
  border: none;
  margin-left: auto;
}

.btn-back-quick-fill {
  color: #888;
  padding: 0;
  border: none;
}
.preview-group-button {
  margin-left: 10px;
  margin-right: 10px;
}
.button-priority {
  background-color: #FEF2F3;
  color: #C56363;
}


.iframe-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.iframe {
  border: none;
  flex-grow: 1;
  margin: 0;
  padding: 0;
}

.custom-badges {
  background: var(--Color-Grey-200, #E9ECF1);
  font-weight: normal;
  color: var(--dark);
}

</style>
