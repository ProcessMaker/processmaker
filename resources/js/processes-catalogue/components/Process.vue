<template>
  <div class="process-info-main" v-if="selectedProcess">
    <div class="mobile-process-nav bg-primary">
      <div class="left">
        <a href="#" @click.prevent="goBackCategory">
          <i class="fas fa-arrow-left" />
        </a>
      </div>
      <div class="center">
        <a href="#" @click.prevent="showDetails = !showDetails">
          <i class="fas fa-info-circle" />
        </a>
      </div>
      <div class="right">
        <bookmark
          :process="selectedProcess"
        />
      </div>
    </div>
    <div class="mobile-process-details" :class="{ 'active' : showDetails }">
      <process-description :process="selectedProcess" />
      <process-counter :process="selectedProcess" />
    </div>

    <ProcessInfo
      v-if="!verifyScreen"
      :process="selectedProcess"
      @goBackCategory="goBackCategory"
    />
    <ProcessScreen
      v-if="verifyScreen"
      :process="selectedProcess"
      @goBackCategory="goBackCategory"
    />
  </div>
</template>

<script>
import ProcessInfo from "./ProcessInfo.vue";
import ProcessScreen from "./ProcessScreen.vue";
import MiniPieChart from "./MiniPieChart.vue";
import Bookmark from "./Bookmark.vue";
import ProcessDescription from "./optionsMenu/ProcessDescription.vue";
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";

export default {
  props: ["process", "processId"],
  components: {
    ProcessInfo, ProcessScreen, MiniPieChart, Bookmark, ProcessDescription, ProcessCounter
  },
  data() {
    return {
      loadedProcess: null,
      showDetails: false,
    };
  },
  computed: {
    /**
     * if we pass in a process, use that. Otherwise load the process by ID
     **/
    selectedProcess() {
      if (this.process) {
        return this.process;
      }

      if (this.loadedProcess) {
        return this.loadedProcess;
      }

      return null;
    },
    /**
     * Verify if the process open the info or Screen
     */
     verifyScreen() {
      let screenId = 0;
      const unparseProperties = this.selectedProcess?.launchpad?.properties || null;
      if (unparseProperties !== null) {
        screenId = JSON.parse(unparseProperties)?.screen_id || 0;
      }

      return screenId !== 0;
    },
    processVersion() {
      return moment(this.selectedProcess.updated_at).format();
    },
    startedCases() {
      return this.selectedProcess.counts?.total || 0;
    },
  },
  methods: {
    goBackCategory() {
      this.$emit("goBackCategory");
    },
  },
  mounted() {
    if (!this.process && this.processId) {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.processId}`)
        .then((response) => {
          this.loadedProcess = response.data[0];
        });
    }

    if (window.ProcessMaker?.navbarMobile) {
      window.ProcessMaker.navbarMobile.display = false;
    }
  },
  beforeDestroy() {
    if (window.ProcessMaker?.navbarMobile) {
      window.ProcessMaker.navbarMobile.display = true;
    }
  },
};
</script>

<style lang="scss" scoped>
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
 
  .left i, .center i {
    display: block;
    color: #FFFFFF;
    padding: 1em;
    font-size: 1.5em;
  }

  .right i {
    padding: 1em;
    -webkit-text-stroke-width: 0;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
    flex-direction: row;
  }
}

.mobile-process-details {
  display: none;

  width: 100%;
  position: absolute;
  z-index: 5;
  padding: 1em;
  background-color: white;
  box-shadow: 0px 8px 8px #00000021;

  &.active {
    display: block;
  }

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
}</style>