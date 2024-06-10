<template>
  <div class="process-info-main">
    <div class="mobile-process-nav bg-primary">
      <div class="left">
        <a href="#" @click.prevent="goBackCategory">
          <i class="fas fa-arrow-left" />
        </a>
      </div>
      <div>
        <a href="#" @click.prevent="showDetails = !showDetails">
          <i class="fas fa-info-circle" />
        </a>
      </div>
      <div class="right">
        <a href="#">
          <i class="fas fa-bookmark" />
        </a>
      </div>
    </div>
    <div class="mobile-process-details" v-if="showDetails">
      <h1>{{ $t('Details') }}</h1>
      <h2>{{ process.name }}</h2>
      <p>{{ process.description }}</p>
      <h3>{{ $t('Version') }} {{ processVersion }}</h3>
      <h1 class="started-cases-title">{{ $t('Started Cases') }}</h1>
      <p class="started-cases">{{ startedCases.toLocaleString() }}</p>
      <div class="charts">
        <mini-pie-chart
          :count="process.counts?.in_progress"
          :total="startedCases"
          :name="$t('In Progress')"
          color="#4EA075"
        />
        <mini-pie-chart
          :count="process.counts?.completed"
          :total="startedCases"
          :name="$t('Completed')"
          color="#478FCC"
        />
      </div>
    </div>
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
import MiniPieChart from "./MiniPieChart.vue";

export default {
  components: {
    ProcessCollapseInfo,
    ProcessTab,
    ProcessesCarousel,
    MiniPieChart,
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
      showDetails: false,
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

    if (window.ProcessMaker?.navbarMobile) {
      window.ProcessMaker.navbarMobile.display = false;
    }
  },
  beforeDestroy() {
    if (window.ProcessMaker?.navbarMobile) {
      window.ProcessMaker.navbarMobile.display = true;
    }
  },
  computed: {
    processVersion() {
      return moment(this.process.updated_at).format();
    },
    startedCases() {
      return this.process.counts?.total || 0;
    },
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
    },
  },
};
</script>

<style scoped lang="scss">
@import '~styles/variables';
.process-info-main {
  overflow-y: auto;
  position: relative;
}

.mobile-process-nav {
  display: none;

  div {
    flex: 1;
    text-align: center;
  }

  .left {
    text-align: left;
  }

  .right {
    text-align: right;
  }
 
  i {
    display: block;
    color: #FFFFFF;
    padding: 1em;
    font-size: 1.5em;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
    flex-direction: row;
  }
}

.mobile-process-details {
  @media (min-width: $lp-breakpoint) {
    display: none;
  }

  width: 100%;
  position: absolute;
  z-index: 5;
  padding: 1em;
  background-color: white;
  box-shadow: 0px 8px 8px #00000021;

  h1 {
    font-size: 1.5em;
    font-weight: 600;
    color: $primary;
  }

  h2 {
    font-size: 1em;
    font-weight: 600;
    color: $primary;
  }

  p {
    font-size: 1em;
  }

  p.started-cases {
    font-size: 3em;
  }

  h1.started-cases-title {
    margin-bottom: 0;
    padding-top: 0.5em;
  }

  h3 {
    font-size: 1em;
    color: $secondary;
  }
}

.charts {
  display: flex;
  align-items: center;
}
</style>
