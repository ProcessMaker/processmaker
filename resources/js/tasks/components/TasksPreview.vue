<template>
  <div>
    <splitpane-container v-if="showPreview" :size="splitpaneSize">
      <div
        id="tasks-preview"
        ref="tasks-preview"
        class="h-100 p-3"
      >
        <div>
          <div class="d-flex w-100 h-100 mb-3">
            <slot name="header" v-bind:close="onClose" v-bind:taskId="task.id">
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
                    v-b-tooltip.hover title="Erase Draft"
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
          <div class="frame-container">
            <b-embed
              v-if="showFrame1"
              ref="tasksFrame1"
              id="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              :src="linkTasks1"
              @load="frameLoaded()"
            />
            <b-embed
              v-if="showFrame2"
              ref="tasksFrame2"
              id="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
              :src="linkTasks2"
              @load="frameLoaded()"
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
      },
    },
  },
  mounted() {
    window.addEventListener('dataUpdated', (event) => {
      this.formData = event.detail;
      this.handleAutosave();
    });
    this.$root.$on('pane-size', (value) => {
      this.size = value;
    });
    this.screenWidthPx = window.innerWidth;
    window.addEventListener('resize', this.updateScreenWidthPx);
  },
  methods: {
    fillWithQuickFillData(data) {
      const message = this.$t('Task Filled succesfully');
      this.sendEvent("fillData", data);
      this.showUseThisTask = false;
      ProcessMaker.alert(message, 'success');
    },
    sendEvent(name, data)
    {
      const event = new CustomEvent(name, {
        detail: data
      });
      if(this.showFrame1) {
        document
        .getElementById("tasksFrame1")
        .contentWindow.dispatchEvent(event);
      }
      if(this.showFrame2) {
        document
        .getElementById("tasksFrame2")
        .contentWindow.dispatchEvent(event);
      }
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
