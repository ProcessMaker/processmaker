<template>
  <div>
    <splitpanes
      v-if="showPreview"
      ref="inspectorSplitPanes"
      class="splitpane default-theme"
      :dbl-click-splitter="false"
    >
      <pane style="opacity: 0;">
        <div />
      </pane>
      <pane
        class="pane-task-preview"
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
          <div v-show="!showQuickFillPreview">
            <div class="d-flex w-100 h-100 mb-3">
              <div class="my-1">
                <a class="lead text-secondary font-weight-bold">
                  {{ task.element_name }}
                </a>
              </div>
              <div class="ml-auto mr-0 text-right">
                <b-button 
                  class="icon-button"
                  :aria-label="$t('Quick fill')"
                  variant="light"
                  @click="goQuickFill()"
                >
                  <img src="../../../img/smartinbox-images/fill.svg" :alt="$t('No Image')">
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
        <quick-fill-preview 
          v-if="showQuickFillPreview" 
          class="quick-fill-preview"
          :task="task"
          :data="data"
          @quick-fill-data="fillWithQuickFillData"
          @close="showQuickFillPreview = false"
        ></quick-fill-preview>
        </div>
      </pane>
    </splitpanes>
  </div>
</template>

<script>
import { Splitpanes, Pane } from "splitpanes";
import TaskLoading from "./TaskLoading.vue";
import PreviewMixin from "./PreviewMixin";
import QuickFillPreview from "./QuickFillPreview.vue";
import "splitpanes/dist/splitpanes.css";

export default {
  components: { Splitpanes, Pane, TaskLoading, QuickFillPreview },
  mixins: [PreviewMixin],
  updated() {
    const resizeOb = new ResizeObserver((entries) => {
      const { width } = entries[0].contentRect;
      this.setPaneMinSize(width, 480);
    });
    if (this.$refs.inspectorSplitPanes) {
      resizeOb.observe(this.$refs.inspectorSplitPanes.container);
    }
  },
  mounted () {
    window.addEventListener('message', (event) => {
      this.data = event.data;
    });
  },
  methods: {
    fillWithQuickFillData(data) {
      document.getElementById("tasksFrame1").contentWindow.postMessage(data, "*");
    },
  },
};
</script>

<style>
.splitpane {
  top: 0;
  min-height: 80vh;
  width: 99%;
  position: absolute;
}
.pane-task-preview {
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
  padding: 10px;
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
