<template>
  <div class="tw-relative tw-h-full">
    <process-collapse-info
      :process="process"
      :current-user-id="currentUserId"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      :my-cases-columns="myCasesColumns"
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
      :is-wizard-template="createdFromWizardTemplate"
      @getHelperProcess="getHelperProcess"
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
          />
        </div>
      </div>
    </slide-process-info>
    <wizard-helper-process-modal
      v-if="createdFromWizardTemplate"
      id="wizardHelperProcessModal"
      ref="wizardHelperProcessModal"
      :process-launchpad-id="process.id"
      :wizard-template-uuid="wizardTemplateUuid"
    />
  </div>
</template>

<script>
import ProcessCollapseInfo from "./ProcessCollapseInfo.vue";
import ProcessTab from "./ProcessTab.vue";
import CarouselSlide from "./CarouselSlide.vue";
import SlideProcessInfo from "./slideProcessInfo/SlideProcessInfo.vue";
import ProcessOptions from "./ProcessOptions.vue";
import WizardHelperProcessModal from "../../components/templates/WizardHelperProcessModal.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    SlideProcessInfo,
    ProcessOptions,
    CarouselSlide,
    WizardHelperProcessModal,
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
      myCasesColumns: [],
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
    createdFromWizardTemplate() {
      return !!this.process?.properties?.wizardTemplateUuid;
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
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
    this.getMyColumns();
  },
  methods: {
    closeFullCarousel() {
      this.fullCarousel = false;
    },
    updateMyTasksColumns(columns) {
      this.myTasksColumns = columns;
      this.$refs.processTab.updateColumnsByType("myTasks", columns);
    },
    getMyColumns() {
      this.$nextTick(() => {
        this.myTasksColumns = this.$refs.processTab.getDefaultColumnsByType("myTasks");
        this.myCasesColumns = this.$refs.processTab.getDefaultColumns("myCases");
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
    getHelperProcess() {
      this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
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
