<template>
  <div class="d-flex justify-content-center">
    <p-tabs :sidenav="sidenav" class="custom-export-container mx-4">
      <template v-slot:default="slotProps">
        <p-tab :active="slotProps.activeIndex === 0">
          <MainAssetView
            :process-info="$root.rootAsset"
            :groups="$root.groups"
            :process-name="$root.rootAsset.name"
            :process-id="processId"
          />
        </p-tab>
        <p-tab v-for="(group, i) in $root.groups" :key="i" :active="slotProps.activeIndex === i + 1">
          <DependentAssetView
            :group="group"
            :items="group.items"
            :process-name="$root.rootAsset.name"
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
    };
  },
  computed: {
    sidenav() {
      const items = [
        { title: this.$root.rootAsset.name, icon: null },
      ];

      this.$root.groups.forEach((group) => {
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
      this.$root.rootAsset = formatted.root;
      this.$root.groups = formatted.groups;
    } else if (!Object.entries(this.$root.manifest).length) {
      // If manifest was not loaded, we try to get it again (if the page is reloaded
      // in custom export, the manifest is empty, so we need to retrieve it again)
      this.$root.getManifest(this.processId);
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
