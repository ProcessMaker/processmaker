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
        v-if="selectedItem && !emailListenerConfigurationComponent"
        :key="setListingKey"
        ref="listings"
        :group="group"
        @refresh="refresh"
        @refresh-all="refreshAll"
      />
      <component
        :is="emailListenerConfigurationComponent"
        ref="emailListenerConfiguration"
        :setting-id="settingId"
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
      settingId: null,
      group: "",
      setListingKey: 0,
      selectedItem: false,
      isEmailStartEventInstalled: false,
    };
  },
  computed: {
    isEmailStartEventInstalled() {
      return !!window.ProcessMaker.EmailStartEvent;
    },
    emailListenerConfigurationComponent() {
      if (this.isEmailStartEventInstalled && this.group.startsWith('Email Listener')) {
        return window.ProcessMaker.EmailStartEvent.EmailListenerConfiguration;
      }

      return null;
    },
  },
  methods: {
    selectGroup(item) {
      this.isEmailStartEventInstalled = !!window.ProcessMaker.EmailStartEvent;

      this.group = item.name;
      this.settingId = item.setting_id;
      this.selectedItem = true;
      this.reRender();
    },
    reRender() {
      this.setListingKey += 1;
    },
    refresh() {
      this.$refs["menu-collapse"].refresh();
    },
    refreshAll() {
      this.$refs["menu-collapse"].refresh();
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
