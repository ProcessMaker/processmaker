<template>
  <div>
    <container :sidenav="sidenav">
      <template v-slot:default="slotProps">
        <container-page :active="slotProps.activeIndex === 0">
          <ProcessesView
            :process-info="rootAsset"
            :process-name="processName"
            @processesView="showProcessesView"
          />
        </container-page>
        <container-page v-for="(group, i) in groups" :key="i" :active="slotProps.activeIndex === i + 1">
          <ScriptsView
            :type="group.type"
            :items="group.items"
            :process-name="processName"
          />
        </container-page>
      </template>
    </container>
  </div>
</template>

<script>
import { Container, ContainerPage } from "SharedComponents";
import ProcessesView from "./process-elements/ProcessesView.vue";
import ScriptsView from "./process-elements/ScriptsView.vue";
import DataProvider from "../DataProvider";

const ICONS = {
  User: "user",
  Group: "users",
  Screen: "file-alt",
  Script: "code",
  Process: "play-circle",
  Category: "",
  EnvironmentVariable: "lock",
  Signal: "bpmn-icon-end-event-signal",
  DataConnector: "cog",
  Vocabulary: "book",
};

export default {
  components: {
    ProcessesView,
    ScriptsView,
    Container,
    ContainerPage,
  },
  props: ["processName",
    "processId",
  ],
  mixins: [],
  data() {
    return {
      currentProcessElement: "ProcessesView",
      processElements: ["ProcessesView",
        "ScriptsView",
        "ScreensView",
        "EnvironmentVariablesView",
        "SignalsView",
        "DataConnectorsView",
        "VocabulariesView"],
      processInfo: {},

      rootAsset: {},
      groups: [],
    };
  },
  computed: {
    sidenav() {
      console.log("GROUP", this.groups);
      let items = [
        { title: this.rootAsset.name, icon: null, },
      ];

      this.groups.forEach(group => {
        items.push({
          title: group.type,
          icon: ICONS[group.type] || null
        });
      });

      return items;
    },
  },
  mounted() {
    DataProvider.getManifest(this.processId)
      .then((response) => {
        console.log("OUTPUT from data provider", response);
        // console.log('response', response);
        // let payload = response.data;
        // console.log('payload', payload);
        // let manifest = payload.manifest;
        // console.log('manifest', manifest);
        // let rootUuid = manifest.root;
        // console.log('rootUuid', rootUuid);
        // this.processInfo = manifest.export[rootUuid];
        // console.log(this.processInfo);
        this.rootAsset = response.root;
        console.log('rootAsset', this.rootAsset);
        this.groups = response.groups;
      })
      .catch((error) => {
        console.log(error);
        ProcessMaker.alert(error, "danger");
      });
  },
  methods: {
    showProcessesView() {
      this.$refs.container.goTo(0);
      // this.$refs.home.setToActive();
      // this.currentProcessElement = ProcessesView;
    },
    showScriptsView() {
      this.currentProcessElement = ScriptsView;
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../../sass/variables";

.custom-export-container {
    // max-width: 1600px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
