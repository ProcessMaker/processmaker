<template>
  <div
    id="asset-quick-create"
    class="my-2"
  >
    <a
      class="asset-link"
      @click="goToAsset"
    >{{ $t("Create a new") }} {{ label }} <i class="fa fa-plus" /></a>
  </div>
</template>
<script>
import { kebabCase } from "lodash";

const AssetTypes = Object.freeze({
  SCREEN: "screen",
  SCRIPT: "script",
  DECISION_TABLE: "decision table",
});

const channel = new BroadcastChannel("assetCreation");

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
  mounted() {
    channel.addEventListener("message", ({ data }) => {
      this.$emit("asset", data);
    });
  },
  methods: {
    goToAsset() {
      return window.open(`/designer/${kebabCase(this.label)}s?create=true`, "_blank");
    },
  },
};
</script>
<style lang="scss" scoped>
#asset-quick-create {
  .asset-link {
    &:hover {
      cursor: pointer;
      color: #054779;
    }
  }
}
</style>
