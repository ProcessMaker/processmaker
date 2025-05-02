<template>
  <div>
    <b-form-select
      v-model="stage"
      class="ml-2"
      :options="[
        { value: 1, text: $t('First stage') },
        { value: 2, text: $t('Second stage') },
        { value: 3, text: $t('Third stage') }
      ]" />

    <process-collapse-info
      v-show="hideLaunchpad"
      :process="process"
      :current-user-id="currentUserId"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @goBackCategory="$emit('goBackCategory')"
      @updateMyTasksColumns="updateMyTasksColumns" />

    <div v-if="stage === 1">
      <BaseCardButtonGroup :data="data1" />
      <process-tab
        v-show="hideLaunchpad"
        ref="processTab"
        :current-user="currentUser"
        :process="process"
        class="process-tab-container" />
    </div>

    <div v-if="stage === 2">
      <PercentageCardButtonGroup :data="percentageData" />
      <process-tab
        v-show="hideLaunchpad"
        ref="processTab"
        :current-user="currentUser"
        :process="process"
        class="process-tab-container" />
    </div>

    <div v-if="stage === 3">
      <ArrowButtonGroup :data="arrowData" />
      <process-tab
        v-show="hideLaunchpad"
        ref="processTab"
        :current-user="currentUser"
        :process="process"
        class="process-tab-container" />
    </div>
    <!--
    <process-tab
      v-show="hideLaunchpad"
      ref="processTab"
      :current-user="currentUser"
      :process="process"
      class="process-tab-container" /> -->

    <div
      v-show="!hideLaunchpad"
      w-100
      h-100>
      <div class="card card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex align-items-center">
            <i
              class="fas fa-angle-left"
              @click="closeFullCarousel" />
            <span style="margin-left: 10px;">{{ process.name }} {{ firstImage }} of {{ lastImage }}</span>
          </div>
        </div>
      </div>
      <processes-carousel
        :process="process"
        :full-carousel="{ url: null, hideLaunchpad: true }"
        :index-selected-image="indexSelectedImage" />
    </div>
  </div>
</template>

<script>
import PercentageCardButtonGroup from "./home/PercentageButtonGroup/PercentageCardButtonGroup.vue";
import BaseCardButtonGroup from "./home/ButtonGroup/BaseCardButtonGroup.vue";
import ArrowButtonGroup from "./home/ArrowButtonGroup/ArrowButtonGroup.vue";
import ProcessCollapseInfo from "./ProcessCollapseInfo.vue";
import ProcessTab from "./ProcessTab.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    ProcessesCarousel,
    BaseCardButtonGroup,
    PercentageCardButtonGroup,
    ArrowButtonGroup,
  },
  props: ["process", "currentUserId", "currentUser", "ellipsisPermission"],
  data() {
    return {
      stage: 1,
      listCategories: [],
      selectCategory: 0,
      dataOptions: {},
      hideLaunchpad: true,
      firstImage: 0,
      lastImage: null,
      indexSelectedImage: 0,
      myTasksColumns: [],
      data1: [
        {
          id: "1",
          header: "Max amount available",
          body: "Across 10 aplicants",
          icon: "fas fa-reply",
          content: "84K",
          active: true,
        },
        {
          id: "2",
          header: "Application awarded",
          body: "30% of all submitted",
          icon: "fas fa-user",
          content: "3",
          color: "amber",
          active: false,
        },
        {
          id: "3",
          header: "Total amount awarded",
          body: "Across 3 aplicants",
          icon: "fas fa-user",
          content: "46K+",
          color: "green",
          active: false,
        },
      ],
      percentageData: [
        {
          id: "1",
          header: "Grants",
          body: "40%",
          percentage: 40,
          content: "28,678",
          color: "amber",
        },
        {
          id: "2",
          header: "Scholarships",
          body: "20%",
          percentage: 20,
          content: "4,678",
          color: "green",
        },
        {
          id: "3",
          header: "Loans",
          body: "15%",
          percentage: 15,
          content: "11,678",
          color: "blue",
        },
        {
          id: "4",
          header: "Out of pocket remaining",
          body: "25%",
          percentage: 25,
          content: "17,649",
          color: "red",
        },
      ],
      arrowData: [
        {
          id: "1",
          body: "Grants",
          header: "40%",
          float: "28K",
          percentage: 40,
          content: "28,678",
          color: "amber",
        },
        {
          id: "2",
          body: "Scholarships",
          header: "20%",
          float: "4K",
          percentage: 20,
          content: "4,678",
          color: "green",
        },
        {
          id: "3",
          body: "Loans",
          header: "15%",
          float: "11K",
          percentage: 15,
          content: "11,678",
          color: "blue",
        },
        {
          id: "4",
          body: "Out of pocket remaining",
          header: "25%",
          float: "17K",
          percentage: 25,
          content: "17,649",
          color: "red",
        },
        {
          id: "4",
          body: "Out of pocket remaining",
          header: "25%",
          float: "17K",
          percentage: 25,
          content: "17,649",
          color: "red",
        },
        {
          id: "4",
          body: "Out of pocket remaining",
          header: "25%",
          float: "17K",
          percentage: 25,
          content: "17,649",
          color: "red",
        },
      ],
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
  },
};
</script>
<style lang="scss" scoped>
@import '~styles/variables';

.process-tab-container {
  @media (min-width: $lp-breakpoint) {
    margin-top: 16px;
    margin-right: 20px;
  }
}
</style>
