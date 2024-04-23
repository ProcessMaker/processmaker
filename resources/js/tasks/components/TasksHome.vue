<template>
  <b-sidebar
    id="form-preview"
    ref="sidebar"
    v-model="showPreview"
    width="50%"
    shadow
    right
  >
    <template #header>
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
          @click="openTask()"
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
    </template>
    <div class="px-3 py-2">
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
  </b-sidebar>
</template>

<script>
import TaskLoading from "./TaskLoading.vue";
import PreviewMixin from "./PreviewMixin";

export default {
  components: { TaskLoading },
  mixins: [PreviewMixin],
};
</script>

<style scoped>
#form-preview {
  width: 50%;
  top: 7%;
}
.loadingFrame {
  opacity: 0.5;
}
.frame-container {
  display: grid;
  height: 80vh;
}
.embed-responsive,
.load-frame {
  position: relative;
  display: grid;
  width: 100%;
  padding: 0;
  overflow: auto;
  grid-row-start: 1;
  grid-column-start: 1;
}
</style>
