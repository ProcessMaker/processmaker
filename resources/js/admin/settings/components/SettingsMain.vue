<template>
  <div
    class="d-flex"
  >
    <div
      id="menu"
      class="menu"
    >
      <settings-menu-collapse
        @selectGroup="selectGroup"
      />      
    </div>
    <div class="setting-info">
      <settings-listing
        v-if="selectedItem"
        :key="setListingKey"
        :group="group"
        @refresh="refresh"
        @refresh-all="refreshAll"
        ref="listings"
      />
      </div>
  </div>
</template>

<script>
import SettingsListing from './SettingsListing';
import SettingsMenuCollapse from './SettingsMenuCollapse';

export default {
  components: { SettingsListing, SettingsMenuCollapse },
  data() {
    return {
      currentTab: 0,
      group: "",
      setListingKey: 0,
      selectedItem: false,
    }
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
      this.apiGet().then(response => {
        response.data.data.forEach(group => {
          if (! this.groups.includes(group.group)) {
            this.groups.push(group.group);
          }
        });
        this.groups.forEach((group, index) => {
          let match = response.data.data.find(serverGroup => serverGroup.group === group);
          if (!match) {
            this.groups.splice(index, 1);
          }
        });
        this.groups.sort();
        this.selectTab();
        this.$emit('groups-refreshed');
      });
    },
    refreshAll() {
      if (Array.isArray(this.$refs.listings)) {
        this.$refs.listings.forEach(listing => {
          listing.refresh();
        })
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
  height: calc(100vh - 155px);
  overflow-y: scroll;
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
}
</style>
