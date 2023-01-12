<template>
  <div class="sidebar-nav-container">
    <div class="process-name-container">
      <li class="font-weight-bold sidebar-nav">
        <b-link
          :aria-expanded="showProcessElements ? 'true' : 'false'"
          class="sidebar-nav"
        >
          <div class="d-flex justify-content-between align-items-center p-2 active">
            <span>{{ processName }}</span>
            <i
              class="fa chevron"
              :class="{
                'fa-chevron-right': !showProcessElements,
                'fa-chevron-down': showProcessElements,
              }"
              style="width: 14px"
              @click="showProcessElements = !showProcessElements"
            />
          </div>
        </b-link>
      </li>
    </div>
    <div>
      <b-collapse id="processelements" v-model="showProcessElements" :aria-hidden="showProcessElements ? 'false' : 'true'">
        <b-link
          class="sidebar-nav"
          @click="onScripts"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon fa-code sidebar-nav" />
            <span>{{ $t("Scripts") }}</span>
          </li>
        </b-link>
        <b-link
          class="sidebar-nav"
          @click="onScreens"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon fa-file-alt sidebar-nav" />
            <span>{{ $t("Screens") }}</span>
          </li>
        </b-link>
        <b-link
          class="sidebar-nav"
          @click="onEnvironmentVariables"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon fa-lock sidebar-nav" />
            <span>{{ $t("Environment Variables") }}</span>
          </li>
        </b-link>
        <b-link
          class="sidebar-nav"
          @click="onSignals"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon bpmn-icon-end-event-signal" />
            <span>{{ $t("Signals") }}</span>
          </li>
        </b-link>
        <b-link
          class="sidebar-nav"
          @click="onDataConnectors"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon fa-cog" />
            <span>{{ $t("Data Connectors") }}</span>
          </li>
        </b-link>
        <b-link
          class="sidebar-nav"
          @click="onVocabularies"
        >
          <li class="nav-list-item">
            <i class="fas nav-icon fa-book" />
            <span>{{ $t("Vocabularies") }}</span>
          </li>
        </b-link>
      </b-collapse>
    </div>
  </div>
</template>

<script>
export default {
  components: {},
  props: ["processName"],
  data() {
    return {
      showProcessElements: false,
    };
  },
  mounted() {
  },
  methods: {
    onScripts() {
      this.$emit("scriptsView");
    },
    onScreens() {
      this.$emit("screensView");
    },
    onEnvironmentVariables() {
      this.$emit("environmentVariablesView");
    },
    onSignals() {
      this.$emit("signalsView");
    },
    onDataConnectors() {
      this.$emit("dataConnectorsView");
    },
    onVocabularies() {
      this.$emit("vocabulariesView");
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.sidebar-nav-container {
    padding-left: 0;
}

.process-name-container {
  text-align: left;
  width: 100%;
}

a.sidebar-nav {
  color: #42526E;
}

li {
  list-style: none;
  text-align: left;
  width: 100%;
  &:hover {
    background-color: #f7f9fb;
  }
}

.nav-list-item {
  padding-top: 0.5em;
  padding-bottom: 0.5em;
  padding-left: 0.5em;
}

.active {
    background-color: $grey-bg;
    border-radius: 0.125em;
}

.active::after {
  position: absolute;
  left: 0.4em;
  width: 4px;
  height: 24px;
  content: "";
  background-color: #0872c2;
  border-radius: 6px;
}

</style>
