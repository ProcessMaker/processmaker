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
                  @click="showQuickFillPreview = true"
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
import PreviewMixin from "./PreviewMixin";
import QuickFillPreview from "./QuickFillPreview.vue";

export default {
  components: { SplitpaneContainer, TaskLoading, QuickFillPreview },
  mixins: [PreviewMixin],
  mounted () {
    console.log("prop task: ", this.task);
    window.addEventListener('dataUpdated', (event) => {
      this.data = event.detail;
    });
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
    }
  }
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
