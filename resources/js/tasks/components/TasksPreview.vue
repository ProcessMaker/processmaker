<template>
  <div v-if="showPreview">
    
    <div v-if="tooltipButton === 'inboxRules'">
      <splitpane-container  :size="50" :class-inbox="true">
        <div
        id="tasks-preview"
        ref="tasks-preview"
        class="w-100 h-100 p-3"
      >
        <div>
          <div class="d-flex w-100 h-100 mb-3">
             <slot name="header" v-bind:close="onClose" v-bind:screenFilteredTaskData="formData"></slot>
          </div>
          <div :class="{
            'frame-container': tooltipButton === 'previewTask' || tooltipButton === '',
            'frame-container-full': tooltipButton === 'fullTask',
            'frame-container-inbox': tooltipButton === 'inboxRules'
            }">
            <b-embed
              v-if="showFrame1"
              ref="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              :src="linkTasks1"
              @load="frameLoaded('tasksFrame1')"
              :event-parent-id="_uid"
            />
            <b-embed
              v-if="showFrame2"
              ref="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
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
      </div>
          </splitpane-container>
    </div>

    <div v-else>
    <splitpane-container v-if="showPreview" :size="splitpaneSize">
      <div
        id="tasks-preview"
        ref="tasks-preview"
        class="h-100 p-3"
      >
        <div>
          <div class="d-flex w-100 h-100 mb-3">
            <slot name="header" v-bind:close="onClose" v-bind:screenFilteredTaskData="formData">
              <b-button-group>
                <b-button
                  class="arrow-button"
                  variant="outline-secondary"
                  :disabled="!existPrev"
                  @click="goPrevNext('Prev')"
                >
                  <i class="fas fa-chevron-left" />
                </b-button>
                <b-button
                  class="arrow-button"
                  variant="outline-secondary"
                  :disabled="!existNext"
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
                    :aria-label="$t('Erase')"
                    variant="light"
                    v-b-tooltip.hover title="Clear Draft"
                    @click="eraseDraft()"
                  >
                    <img src="/img/smartinbox-images/eraser.svg" :alt="$t('No Image')">
                  </b-button>
                  <b-button
                    v-if="showQuickFillPreview === false"
                    class="icon-button"
                    :aria-label="$t('Quick fill')"
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
                    v-if="isAllowReassignment || isUserAdmin || isProcessManager"
                    class="btn text-secondary icon-button"
                    variant="light"
                    :aria-label="$t('Reassign')"
                    @click="openReassignment()"
                  >
                    <i class="fas fa-user-friends" />
                  </b-button>
                  <b-button
                    class="btn text-secondary icon-button"
                    variant="light"
                    :aria-label="$t('Open Task')"
                    @click="openTask()"
                  >
                    <i class="fas fa-external-link-alt" />
                  </b-button>
                </b-button-group>

                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Close')"
                  @click="onClose()"
                >
                  <i class="fas fa-times" />
                </b-button>
              </div>
            </slot>
          </div>
          <div
            id="reassign-container"
            class="d-flex w-100 h-100 mb-3 align-items-center"
            v-if="showReassignment"
          >
            <div class="mr-3">
              <label for="user">Assign to:</label>
            </div>
            <div class="flex-grow-1">
              <select-from-api
                id='user'
                v-model="selectedUser"
                :placeholder="$t('Type here to search')"
                api="users"
                :multiple="false"
                :show-labels="false"
                :searchable="true"
                :store-id="false"
                label="fullname"
              >
                <template slot="noResult">
                  {{ $t('No elements found. Consider changing the search query.') }}
                </template>
                <template slot="noOptions">
                  {{ $t('No Data Available') }}
                </template>
                <template slot="tag" slot-scope="props">
                  <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                    <span class="option__desc mr-1">
                      <span class="option__title">{{ props.option.fullname }}</span>
                    </span>
                    <i aria-hidden="true" tabindex="1"
                      @click="props.remove(props.option)"
                      class="multiselect__tag-icon"></i>
                  </span>
                </template>
                <template slot="option" slot-scope="props">
                  <div class="option__desc d-flex align-items-center">
                    <span class="option__title mr-1">{{ props.option.fullname }}</span>
                  </div>
                </template>
              </select-from-api>
            </div>
            <button type="button" class="btn btn-secondary ml-2" @click="reassignUser" :disabled="disabled">
              {{ $t('Assign') }}
            </button>
            <button type="button" class="btn btn-outline-secondary ml-2" @click="cancelReassign">
              {{ $t('Cancel') }}
            </button>
          </div>
          <div :class="{
            'frame-container': tooltipButton === 'previewTask' || tooltipButton === '',
            'frame-container-full': tooltipButton === 'fullTask',
            'frame-container-inbox': tooltipButton === 'inboxRules'
          }">
            <b-embed
              v-if="showFrame1"
              ref="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              :src="linkTasks1"
              :event-parent-id="_uid"
              @load="frameLoaded('tasksFrame1')"
            />
            <b-embed
              v-if="showFrame2"
              ref="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
              :src="linkTasks2"
              :event-parent-id="_uid"
              @load="frameLoaded('tasksFrame2')"
            />

            <task-loading
              v-show="stopFrame"
              class="load-frame"
            />
          </div>
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

export default {
  components: { SplitpaneContainer, TaskLoading, QuickFillPreview, TaskSaveNotification, EllipsisMenu },
  mixins: [PreviewMixin, autosaveMixins],
  props: ["tooltipButton", "propPreview"],
  watch: {
    task: {
      deep: true,
      handler(task) {
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
          this.singleTask();
        }
      },
    },
  },
  mounted() {
    if(this.propPreview){
      this.showPreview = true;
    }
    this.receiveEvent("dataUpdated", (data) => {
      this.formData = data;
      if (this.userHasInteracted) {
        this.handleAutosave();
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
  },
  computed: {
    iframe1ContentWindow() {
      return this.$refs["tasksFrame1"].firstChild.contentWindow;
    },
    iframe2ContentWindow() {
      return this.$refs["tasksFrame2"].firstChild.contentWindow;
    },
    disabled() {
      return this.selectedUser ? this.selectedUser.length === 0 : true;
    },
    isAllowReassignment() {
      if (this.taskDefinition.definition) {
        return this.taskDefinition.definition.allowReassignment === "true";
      }
      return false;
    },
    isUserAdmin() {
      return this.user.is_administrator;
    },
    isProcessManager() {
      return this.task.process_obj.properties.manager_id === ProcessMaker.user.id;
    },
  },
  methods: {
    fillWithQuickFillData(data) {
      const message = this.$t('Task Filled succesfully');
      this.sendEvent("fillData", data);
      this.showUseThisTask = false;
      ProcessMaker.alert(message, 'success');
      this.handleAutosave();
    },
    sendEvent(name, data)
    {
      const event = new CustomEvent(name, {
        detail: data
      });
      if(this.showFrame1) {
        this.iframe1ContentWindow.dispatchEvent(event);
      }
      if(this.showFrame2) {
        this.iframe2ContentWindow.dispatchEvent(event);
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
        });
    },
    eraseDraft() {
      ProcessMaker.apiClient
        .delete("drafts/" + this.task.id)
        .then(() => {
          this.isLoading = setTimeout(() => {
            this.stopFrame = true;
            this.taskTitle = this.$t("Task Lorem");
          }, 4900);
          this.showSideBar(this.task, this.data);
          this.task.draft = null;
        });
    },
    reassignUser() {
      if (this.selectedUser) {
        ProcessMaker.apiClient
          .put("tasks/" + this.task.id, {
            user_id: this.selectedUser.id
          })
          .then(response => {
            this.showReassignment = false;
            this.selectedUser = [];
          });
      }
    },
    cancelReassign() {
      this.showReassignment = false;
      this.selectedUser = [];
    },
    openReassignment() {
      this.showReassignment = !this.showReassignment;
    },
    singleTask() {
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
  },
};
</script>

<style>
#tasks-preview {
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
  height: 70vh;
}
.frame-container-full {
  display: grid;
  height: 70vh;
  width: 93%
}
.frame-container-inbox {
  display: grid;
  height: 70vh;
  width: 98%;
}
.embed-responsive,
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
</style>
