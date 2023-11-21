<template>
  <div
    id="asset-quick-create"
    class="my-2"
  >
    <a
      class="asset-link"
      @click="goToAsset"
    >{{ $t("Create a new") }} {{ label }} <i
      class="fa fa-plus"
    /></a>
  </div>
</template>
<script>
import { capitalize, kebabCase } from "lodash";
import { AssetTypes } from "../../../../models/AssetTypes";
import { ScreenTypes } from "../../../../models/screens";

const channel = new BroadcastChannel("assetCreation");

export default {
  name: "ModelerAssetQuickCreate",
  props: {
    label: {
      type: String,
      default: AssetTypes.SCREEN,
      validator(value) {
        return Object.values(AssetTypes).includes(value);
      },
    },
    screenType: {
      type: String,
      default: ScreenTypes.DISPLAY,
      validator(value) {
        const propValue = value.split(",");
        // Value is coming uppercase, capitalizing it for comparison.
        return Object.values(ScreenTypes).includes(capitalize(...propValue));
      },
    },
    screenSelectId: {
      type: String,
      required: false,
    },
  },
  methods: {
    goToAsset() {
      channel.onmessage = ({ data }) => {
        this.$emit("asset", data);
      };
      let url = `/designer/${kebabCase(
        this.label,
      )}s?create=true&screenSelectId=${this.screenSelectId}`;
      // Cleaning the URL query if the asset is not of type Screen
      if (this.screenType && this.label === AssetTypes.SCREEN) {
        url += `&screenType=${this.screenType}`;
      }
      return window.open(url, "_blank");
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
