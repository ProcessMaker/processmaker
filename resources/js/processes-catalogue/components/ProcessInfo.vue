<template>
  <div class="tw-relative tw-h-full">
    <process-collapse-info
      :process="process"
      :current-user-id="currentUserId"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @goBackCategory="$emit('goBackCategory')"
      @updateMyTasksColumns="updateMyTasksColumns"
      @toggle-info="toggleInfo"
    />
    <process-tab
      v-show="hideLaunchpad"
      ref="processTab"
      :current-user="currentUser"
      :process="process"
      class="tw-mt-4 tw-mr-5 lg:tw-mt-4 lg:tw-mr-5"
    />
    <!-- SlideOver -->
    <slide-process-info
      :show="showProcessInfo"
      :title="title"
      :process="process"
      :full-carousel="fullCarousel"
      @closeCarousel="closeFullCarousel"
      @close="closeProcessInfo"
    >
      <div class="tw-flex tw-flex-col tw-gap-4 tw-pl-10 tw-pr-10">
        <carousel-slide
          :process="process"
          @full-carousel="showFullCarousel"
        />
        <div v-show="!fullCarousel">
          <process-options
            class="tw-w-full"
            :process="process"
            :collapsed="collapsed"
          />
          <progress-bar-section :stages-summary="process.stagesSummary" />
        </div>
      </div>
    </slide-process-info>
  </div>
</template>

<script>
import ProcessCollapseInfo from "./ProcessCollapseInfo.vue";
import ProcessTab from "./ProcessTab.vue";
import CarouselSlide from "./CarouselSlide.vue";
import SlideProcessInfo from "./slideProcessInfo/SlideProcessInfo.vue";
import ProcessOptions from "./ProcessOptions.vue";
import ProgressBarSection from "./progressBar/ProgressBarSection.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    SlideProcessInfo,
    ProcessOptions,
    ProgressBarSection,
    CarouselSlide,
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
      fullCarousel: false,
    };
  },
  computed: {
    title() {
      return this.fullCarousel
        ? this.process.name
        : this.$t("Process Information");
    },
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
      this.fullCarousel = false;
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
    showFullCarousel() {
      this.fullCarousel = true;
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
