<template>
  <div class="d-flex justify-content-center">
    <container :sidenav="sidenav" class="custom-export-container mx-4">
      <template v-slot:default="slotProps">
        <container-page :active="slotProps.activeIndex === 0">
          <ProcessesView
            :process-info="rootAsset"
            :groups="groups"
            :process-name="rootAsset.name"
            :process-id="processId"
          />
        </container-page>
        <container-page v-for="(group, i) in groups" :key="i" :active="slotProps.activeIndex === i + 1">
          <ScriptsView
            :group="group"
            :items="group.items"
            :process-name="rootAsset.name"
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

export default {
  components: {
    ProcessesView,
    ScriptsView,
    Container,
    ContainerPage,
  },
  props: {
    processName: {},
    processId: {},
  },
  mixins: [],
  data() {
    return {
      rootAsset: {},
      groups: [],
    };
  },
  computed: {
    sidenav() {
      let items = [
        { title: this.rootAsset.name, icon: null, },
      ];

      this.groups.forEach(group => {
        items.push({
          title: group.typePlural,
          icon: group.icon,
        });
      });

      return items;
    },
  },
  mounted() {
    if (this.$root.isImport) {
        if (!this.$root.file) {
          this.$router.push({ name: "main" });
          return;
        }

        const formatted = DataProvider.formatAssets(this.$root.manifest, this.$root.rootUuid);
        this.rootAsset = formatted.root;
        this.groups = formatted.groups;
    } else {
      DataProvider.getManifest(this.processId)
        .then((response) => {
          this.rootAsset = response.root;
          this.groups = response.groups;
          this.$root.setInitialState(response.assets, response.rootUuid);
        })
        .catch((error) => {
          console.log(error);
          ProcessMaker.alert(error, "danger");
        });
    }
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>
@import "../../../../sass/variables";

.custom-export-container {
    max-width: 1600px;
    display: block;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
