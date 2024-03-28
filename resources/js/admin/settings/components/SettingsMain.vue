<template>
  <div class="d-flex">
    <div
      id="menu"
      class="menu"
    >
      <settings-menu-collapse
        ref="menu-collapse"
        @selectGroup="selectGroup"
      />
    </div>
    <div class="setting-info pl-3">
      <settings-listing
        v-if="selectedItem"
        :key="setListingKey"
        ref="listings"
        :group="group"
        @refresh="refresh"
        @refresh-all="refreshAll"
      />
    </div>
  </div>
</template>

<script>
import SettingsListing from "./SettingsListing.vue";
import SettingsMenuCollapse from "./SettingsMenuCollapse.vue";

export default {
  components: { SettingsListing, SettingsMenuCollapse },
  data() {
    return {
      currentTab: 0,
      group: "",
      setListingKey: 0,
      selectedItem: false,
    };
  },
  methods: {
    selectGroup(item) {
      this.group = item.name;
      this.selectedItem = true;
      this.reRender();
    },
    reRender() {
      this.setListingKey += 1;
    },
    refresh() {
      this.apiGet().then((response) => {
        response.data.data.forEach((group) => {
          if (!this.groups.includes(group.group)) {
            this.groups.push(group.group);
          }
        });
        this.groups.forEach((group, index) => {
          const match = response.data.data.find((serverGroup) => serverGroup.group === group);
          if (!match) {
            this.groups.splice(index, 1);
          }
        });
        this.groups.sort();
        this.selectTab();
        this.$emit("groups-refreshed");
      });
    },
    refreshAll() {
      if (Array.isArray(this.$refs.listings)) {
        this.$refs.listings.forEach((listing) => {
          listing.refresh();
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("../../../../sass/_scrollbar.scss");
.menu {
  min-width: 304px;
  padding-top: 16px;
  height: calc(100vh - 150px);
  overflow-y: auto;
}
.menu-title {
  color: #556271;
  font-size: 22px;
  font-style: normal;
  font-weight: 600;
  line-height: 46.08px;
  letter-spacing: -0.44px;
}
.setting-info {
  width: 100%;
  margin-right: 16px;
  height: calc(100vh - 150px);
  overflow-y: auto;
  background-color: #fff;
  padding: 16px 16px 106px 16px;
  border-radius: 4px;
  border: 1px solid var(--borders, #CDDDEE);
}
</style>
