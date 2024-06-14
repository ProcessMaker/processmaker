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
      <h1>{{ $t('Details') }}</h1>
      <h2>{{ selectedProcess.name }}</h2>
      <p>{{ selectedProcess.description }}</p>
      <h3>{{ $t('Version') }} {{ processVersion }}</h3>
      <h1 class="started-cases-title">{{ $t('Started Cases') }}</h1>
      <p class="started-cases">{{ startedCases.toLocaleString() }}</p>
      <div class="charts">
        <mini-pie-chart
          :count="selectedProcess.counts?.in_progress"
          :total="startedCases"
          :name="$t('In Progress')"
          color="#4EA075"
        />
        <mini-pie-chart
          :count="selectedProcess.counts?.completed"
          :total="startedCases"
          :name="$t('Completed')"
          color="#478FCC"
        />
      </div>
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

export default {
  props: ["process", "processId"],
  components: {
    ProcessInfo, ProcessScreen, MiniPieChart, Bookmark
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
          console.log("Process.vue got response", response.data[0]);
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

  // border: 4px solid red;

  // transition: top 0.3s;
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