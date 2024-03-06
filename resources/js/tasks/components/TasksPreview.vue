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
                />
                <a class="lead text-secondary font-weight-bold">
                  {{ task.element_name }}
                </a>
              </div>
              <b-tooltip
                target="saved-status"
                custom-class="pm-table-tooltip"
              >
                <div class="">
                  {{ task.process_request.case_title }}
                </div>
              </b-tooltip>
              <div class="ml-auto mr-0 text-right">
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

export default {
  components: { Splitpanes, Pane, TaskLoading, TaskSaveNotification },
  mixins: [PreviewMixin, autosaveMixins],
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
  },
  methods: {
    fillWithQuickFillData(data) {
      document.getElementById("tasksFrame1").contentWindow.postMessage(data, "*");
    },
    autosaveApiCall() {
      this.options.is_loading = true;
      return ProcessMaker.apiClient
        .put("drafts/" + this.task.id, this.formData)
        .then(() => {
        })
        .finally(() => {
          this.options.is_loading = false;
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
.pm-table-tooltip {
  opacity: 1 !important;
}
.pm-table-tooltip .tooltip-inner {
  background-color: #FFFFFF;
  color: #566877;
  box-shadow: -5px 5px 5px rgba(0, 0, 0, 0.3);
  max-width: 250px;
  padding: 14px;
  border-radius: 7px;
}
.pm-table-tooltip .arrow::before {
  border-bottom-color: #F2F8FE !important;
  border-top-color: #F2F8FE !important;
}
</style>
