<template>
  <div>
    <splitpanes
      v-if="showPreview"
      id="splitpane"
      ref="inspectorSplitPanes"
      class="default-theme"
      :dbl-click-splitter="false"
    >
      <pane style="opacity: 0;">
        <div />
      </pane>
      <pane
        id="pane-task-preview"
        :min-size="paneMinSize"
        size="50"
        max-size="99"
        style="background-color: white;"
      >
        <div
          id="tasks-preview"
          ref="tasks-preview"
          class="h-100 p-3"
        >
          <div>
            <div class="d-flex w-100 h-100 mb-3">
              <div id="saved-status" class="my-1">
                <task-save-notification
                  :options="options"
                  :task="task"
                  :date="lastAutosave"
                />
              </div>
              <div class="ml-auto mr-0 text-right">
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
                  class="btn-light text-secondary"
                  :aria-label="$t('Previous Tasks')"
                  :disabled="!existPrev"
                  @click="goPrevNext('Prev')"
                >
                  <i class="fas fa-chevron-left" />
                  {{ $t("Prev") }}
                </b-button>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Next Tasks')"
                  :disabled="!existNext"
                  @click="goPrevNext('Next')"
                >
                  {{ $t("Next") }}
                  <i class="fas fa-chevron-right" />
                </b-button>
                <a class="text-secondary">|</a>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Open Task')"
                  :href="openTask()"
                >
                  <i class="fas fa-external-link-alt" />
                </b-button>
                <a class="text-secondary">|</a>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Close')"
                  @click="onClose()"
                >
                  <i class="fas fa-times" />
                </b-button>
              </div>
            </div>
            <div class="frame-container">
              <b-embed
                v-if="showFrame1"
                id="tasksFrame1"
                width="100%"
                :class="showFrame2 ? 'loadingFrame' : ''"
                :src="linkTasks1"
                @load="frameLoaded()"
              />
              <b-embed
                v-if="showFrame2"
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
        </div>
      </pane>
    </splitpanes>
  </div>
</template>

<script>
import { Splitpanes, Pane } from "splitpanes";
import TaskLoading from "./TaskLoading.vue";
import PreviewMixin from "./PreviewMixin";
import autosaveMixins from "../../modules/autosave/autosaveMixin.js"
import TaskSaveNotification from "./TaskSaveNotification.vue";
import "splitpanes/dist/splitpanes.css";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheckCircle } from "@fortawesome/free-solid-svg-icons";
import _ from 'lodash';
import moment from "moment";

export default {
  components: { Splitpanes, Pane, TaskLoading, TaskSaveNotification, FontAwesomeIcon },
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
      },
    },
  },
  updated() {
    const resizeOb = new ResizeObserver((entries) => {
      const { width } = entries[0].contentRect;
      this.setPaneMinSize(width, 480);
    });
    if (this.$refs.inspectorSplitPanes) {
      resizeOb.observe(this.$refs.inspectorSplitPanes.container);
    }
  },
  mounted() {
    window.addEventListener("message", (event) => {
      if (event.data.typeData === "form-data") {
        this.formData = event.data.data;
        this.handleAutosave();
      }
    });
    this.savedIcon = faCheckCircle;
  },
  methods: {
    fillWithQuickFillData(data) {
      document.getElementById("tasksFrame1").contentWindow.postMessage(data, "*");
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
        .finally(() => {
          this.options.is_loading = false;
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
#splitpane {
  top: 0;
  min-height: 80vh;
  width: 99%;
  position: absolute;
}
#pane-task-preview {
  flex-grow: 1;
  overflow-y: auto;
}
#tasks-preview {
  box-sizing: border-box;
  display: block;
  overflow: hidden;
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
</style>
