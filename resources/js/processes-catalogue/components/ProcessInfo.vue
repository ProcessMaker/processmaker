<template>
  <div class="process-info-main">
    <process-collapse-info
      v-show="hideLaunchpad"
      :process="process"
      :permission="permission"
      :current-user-id="currentUserId"
      :is-documenter-installed="isDocumenterInstalled"
      @goBackCategory="goBackCategory"
    />
    <process-tab
      v-show="hideLaunchpad"
      :current-user="currentUser"
      :process="process"
    />

    <div w-100 h-100 v-show="!hideLaunchpad">
      <div class="card card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex align-items-center">
          <i class="fas fa-angle-left"
          @click="closeFullCarousel"
          />
          <span style="margin-left: 10px;">{{ process.name }} {{ this.firstImage }} of {{ this.lastImage }}</span>
        </div>
      </div>
      </div>
      <processes-carousel
        :process="process"
        :full-carousel="{ url: null, hideLaunchpad: true }"
        :index-selected-image="indexSelectedImage"
      />
    </div>
  </div>
</template>

<script>
import ProcessCollapseInfo from "./ProcessCollapseInfo.vue";
import ProcessTab from "./ProcessTab.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    ProcessesCarousel,
  },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId", "currentUser"],
  data() {
    return {
      listCategories: [],
      selectCategory: 0,
      dataOptions: {},
      hideLaunchpad: true,
      firstImage: 0,
      lastImage: null,
      indexSelectedImage: 0,
    };
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
  },
  methods: {
    /**
     * Return a process cards
     */
    goBackCategory() {
      this.$emit("goBackCategory");
    },
    closeFullCarousel() {
      this.$root.$emit("clickCarouselImage", false);
    }
  },
};
</script>

<style lang="css" scoped>
.process-info-main {
  overflow-y: auto;
}
</style>
