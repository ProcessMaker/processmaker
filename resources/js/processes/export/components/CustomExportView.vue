<template>
  <div class="d-flex justify-content-center">
    <p-tabs :sidenav="sidenav" class="custom-export-container mx-4">
      <template v-slot:default="slotProps">
        <p-tab :active="slotProps.activeIndex === 0">
          <MainAssetView
            :process-info="formattedRoot"
            :groups="formattedGroups"
            :process-name="formattedRoot.name"
            :process-id="processId"
          />
        </p-tab>
        <p-tab v-for="(group, i) in groupsFiltered" :key="i" :active="slotProps.activeIndex === i + 1">
          <DependentAssetView
            :group="group"
            :items="group.items"
            :process-name="formattedRoot.name"
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
    rootAsset: {},
    groups: [],
  },
  mixins: [],
  data() {
    return {
      formattedRoot: {},
      formattedGroups: [],
    };
  },
  computed: {
    sidenav() {
      const items = [
        { title: this.formattedRoot.name, icon: null },
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
      return this.formattedGroups.filter((group) => {
        return this.$root.groupsHaveSomeActive[group.type];
      }).filter((group) => {
        return this.$root.hasSomeNotDiscardedByParent(group.items);
      });
    },
  },
  mounted() {
    if (this.$root.isImport) {
        if (!this.$root.file) {
          this.$router.push({ name: "main" });
          return;
        }
    }
      const formattedRoot = this.rootAsset;
      const formattedGroups = this.groups;
      const formatted = DataProvider.formatAssets(this.$root.manifest, this.$root.rootUuid);
      this.formattedRoot = formatted.root;
      this.formattedGroups = formatted.groups;
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
