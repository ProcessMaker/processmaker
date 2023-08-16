<template>
  <div
    id="asset-quick-create"
    class="mt-2"
  >
    <a
      class="asset-link"
      @click="goToAsset"
    >Create a new {{ label }} <i class="fa fa-plus" /></a>
  </div>
</template>
<script>

const AssetTypes = Object.freeze({
  SCREEN: "screen",
  SCRIPT: "script",
  DECISION_TABLE: "decision table",
});
export default {
  name: "ModelerAssetQuickCreate",
  props: {
    label: {
      type: String,
      default: AssetTypes.SCREEN,
      validator(value) {
        return Object.values(AssetTypes)
          .includes(value);
      },
    },
  },
  methods: {
    goToAsset() {
      return window.open(`/designer/${this.label}?create`, "_blank");
    },
  },
};
</script>
<style lang="scss">
#asset-quick-create {
  .asset-link {
    &:hover {
      color: darken(#0872C2, 5%);
    }
  }
}
</style>
