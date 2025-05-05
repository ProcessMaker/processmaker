<template>
  <div class="tw-relative tw-h-full">
    <process-collapse-info
      :process="process"
      :current-user-id="currentUserId"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @goBackCategory="$emit('goBackCategory')"
      @updateMyTasksColumns="updateMyTasksColumns"
      @toggle-info="toggleInfo" />
    <process-tab
      v-show="hideLaunchpad"
      ref="processTab"
      :current-user="currentUser"
      :process="process"
      class="tw-mt-4 tw-mr-5 lg:tw-mt-4 lg:tw-mr-5" />

    <div
      v-show="!hideLaunchpad"
      class="tw-w-full tw-h-full">
      <div class="tw-card tw-card-body">
        <div class="tw-flex tw-justify-between">
          <div class="tw-flex tw-items-center">
            <i
              class="fas fa-angle-left"
              @click="closeFullCarousel" />
            <span class="tw-ml-2.5">{{ process.name }} {{ firstImage }} of {{ lastImage }}</span>
          </div>
        </div>
      </div>
      <processes-carousel
        :process="process"
        :full-carousel="{ url: null, hideLaunchpad: true }"
        :index-selected-image="indexSelectedImage" />
    </div>

    <!-- SlideOver -->
    <slide-process-info
      :show="showProcessInfo"
      :title="$t('Process Information')"
      @close="closeProcessInfo">
      <div class="tw-flex tw-flex-col tw-gap-4">
        <processes-carousel
          class="tw-w-full"
          :process="process"
          :full-carousel="{ url: null, hideLaunchpad: false }" />

        <process-options
          class="tw-w-full"
          :process="process"
          :collapsed="collapsed" />

        <progress-bar-section :stages-summary="process.stagesSummary"/>
      </div>
    </slide-process-info>
  </div>
</template>

<script>
import ProcessCollapseInfo from "./ProcessCollapseInfo.vue";
import ProcessTab from "./ProcessTab.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";
import SlideProcessInfo from "./slideProcessInfo/SlideProcessInfo.vue";
import ProcessOptions from "./ProcessOptions.vue";
import ProgressBarSection from "./progressBar/ProgressBarSection.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    ProcessesCarousel,
    SlideProcessInfo,
    ProcessOptions,
    ProgressBarSection,
  },
  props: ["process", "currentUserId", "currentUser", "ellipsisPermission"],
  data() {
    return {
      listCategories: [],
      selectCategory: 0,
      dataOptions: {},
      hideLaunchpad: true,
      firstImage: 0,
      lastImage: null,
      indexSelectedImage: 0,
      myTasksColumns: [],
      showProcessInfo: false,
      collapsed: true,
    };
  },
  computed: {
  },
  mounted() {
    this.dataOptions = {
      id: this.process.id.toString(),
      type: "Process",
    };
    this.$root.$on("clickCarouselImage", (val) => {
      this.hideLaunchpad = !val.hideLaunchpad;
      this.showProcessInfo = false;
      this.lastImage = val.countImages;
      this.indexSelectedImage = val.imagePosition;
      this.firstImage = this.indexSelectedImage + 1;
    });
    this.$root.$on("carouselImageSelected", (pos) => {
      this.firstImage = pos + 1;
    });
    this.getMyTasksColumns();
  },
  methods: {
    closeFullCarousel() {
      this.$root.$emit("clickCarouselImage", false);
    },
    updateMyTasksColumns(columns) {
      this.myTasksColumns = columns;
      this.$refs.processTab.updateColumnsByType("myTasks", columns);
    },
    getMyTasksColumns() {
      this.$nextTick(() => {
        this.myTasksColumns = this.$refs.processTab.getDefaultColumnsByType("myTasks");
      });
    },
    toggleInfo() {
      this.showProcessInfo = !this.showProcessInfo;
    },
    closeProcessInfo() {
      this.showProcessInfo = false;
    },
  },
};
</script>
<style lang="scss" scoped>
@import '~styles/variables';
/* Media queries responsive */
@media (min-width: $lp-breakpoint) {
  .tw-mt-4.tw-mr-5 {
    margin-top: 16px;
    margin-right: 20px;
  }
}
</style>
