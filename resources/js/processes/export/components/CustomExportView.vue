<template>
  <div class="d-flex justify-content-center">
    <p-tabs :sidenav="sidenav" class="custom-export-container mx-4">
      <template v-slot:default="slotProps">
        <p-tab :active="slotProps.activeIndex === 0">
          <MainAssetView
            :process-info="rootAsset"
            :groups="groups"
            :process-name="rootAsset.name"
            :process-id="processId"
          />
        </p-tab>
        <p-tab v-for="(group, i) in groupsFiltered" :key="i" :active="slotProps.activeIndex === i + 1">
          <DependentAssetView
            :group="group"
            :items="group.items"
            :process-name="rootAsset.name"
          />
        </p-tab>
      </template>
    </p-tabs>
  </div>
</template>

<script>
import { PTabs, PTab } from "SharedComponents";
import MainAssetView from "./MainAssetView.vue";
import DependentAssetView from "./DependentAssetView.vue";
import DataProvider from "../DataProvider";

export default {
  components: {
    MainAssetView,
    DependentAssetView,
    PTab,
    PTabs,
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
      const items = [
        { title: this.rootAsset.name, icon: null },
      ];

      this.groupsFiltered.forEach(group => {
          items.push({
            title: group.typePlural,
            icon: group.icon,
          });
        });

      return items;
    },
    groupsFiltered()
    {
      return this.groups.filter((group) => {
          return this.$root.groupsHaveSomeActive[group.type];
      });
    }
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
    max-width: 1100px;
    display: block;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
