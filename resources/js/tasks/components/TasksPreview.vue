<template>
  <div>
    <splitpanes
      v-if="showPreview"
      ref="inspectorSplitPanes"
      class="splitpane default-theme"
      :dbl-click-splitter="false"
    >
      <pane style="opacity: 0">
        <div />
      </pane>
      <pane
        class="pane-task-preview"
        :min-size="paneMinSize"
        size="50"
        max-size="99"
        style="background-color: white"
      >
        <div
          id="tasks-preview"
          ref="tasks-preview"
          class="h-100 p-3"
        >
          <div v-show="!showQuickFillPreview">
            <div v-if="!showUseThisTask" 
              class="d-flex w-100 h-100 mb-3">
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
              <div class="my-1 ml-1">
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
                  <img
                    src="../../../img/smartinbox-images/fill.svg"
                    :alt="$t('No Image')"
                  />
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
            <div v-if="showUseThisTask"
              class="d-flex justify-content-between"
            >
            <div class="d-flex align-items-center">
              <b-button
                class="btn-back-quick-fill"
                variant="link"
                @click="showUseThisTask = false"
              >
                <i class="fas fa-arrow-left" />
              </b-button>
              <div class="my-1 ml-2">
                <a class="lead text-secondary font-weight-bold">
                  {{ previewData._request.case_title }}
                </a>
              </div>
            </div>
            <div>
              <b-button
                  v-if="showUseThisTask"
                  class="mr-2"
                  variant="primary"
                  :aria-label="$t('Use This Task Data')"
                  @click="fillWithQuickFillData(previewData)"
                >
                  {{ $t('Use This Task Data') }}
                </b-button>
                <b-button
                  class="close-button mr-2"
                  variant="link"
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
            @quick-fill-data-preview="fillWithPreviewQuickFillData"
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
  mounted () {
    window.addEventListener('dataUpdated', (event) => {
      this.data = event.detail;
    });
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
  methods: {
    fillWithQuickFillData(data) {
      const message = this.$t('Task Filled succesfully');
      this.sendEvent("fillData", data);
      this.showUseThisTask = false;
      ProcessMaker.alert(message, 'success');
    },
    fillWithPreviewQuickFillData(data) {
      this.previewData = data;
      this.showUseThisTask = true;
    },
    sendEvent(name, data)
    {
      const event = new CustomEvent(name, {
        detail: data
      });
      document
        .getElementById("tasksFrame1")
        .contentWindow.dispatchEvent(event);
    }
  }
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
</style>
