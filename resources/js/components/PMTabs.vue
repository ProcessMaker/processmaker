<template>
  <b-card no-body
          class="pm-tabs">
    <b-tabs ref="bTabs"
            v-model="activeTab"
            @changed="$emit('changed', $event)"
            lazy
            :nav-class="{'pl-2': true, 'pm-tabs-nav-class': true, 'pm-tabs-nav-link': true, 'pm-tabs-nav-class-mobile': mobileApp, 'pm-tabs-nav-link-mobile': mobileApp}"
            :active-nav-item-class="{'font-weight-bold': true, 'pm-tabs-nav-class': true, 'pm-tabs-nav-class-mobile': mobileApp}"
            content-class="m-2">
      <template #tabs-start>
        <slot name="tabs-start"></slot>
      </template>
      <slot></slot>
      <template #tabs-end>
        <slot name="tabs-end"></slot>
      </template>
    </b-tabs>
  </b-card>
</template>

<script>
  export default {
    props: {
      value: {
        type: Number,
        default: 0
      }
    },
    data() {
      return {
        activeTab: this.value,
        mobileApp: window.ProcessMaker.mobileApp
      };
    },
    watch: {
      value(newValue) {
        this.activeTab = newValue;
      },
      activeTab(newValue) {
        this.$emit("input", newValue);
      }
    },
    methods: {
      getTabs() {
        return this.$refs.bTabs.tabs;
      },
      getButtons() {
        return this.$refs.bTabs.$refs.buttons;
      }
    }
  };
</script>

<style>
  .pm-tabs {
    border-radius: 0.5em;
  }
  .pm-tabs-nav-class {
    background: #EBF1F7 !important;
    font-size: 15px;
    flex-wrap: nowrap;
    text-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    border-top-left-radius: 0.5em;
    border-top-right-radius: 0.5em;
  }
  .pm-tabs-nav-link .nav-link {
    border-color: #EBF1F7 !important;
    padding-top: 14px;
    padding-bottom: 16px;
  }
  .pm-tabs-nav-class-mobile {
    background: #FFFFFF !important;
  }
  .pm-tabs-nav-link-mobile .nav-link {
    border-right-color: #FFFFFF !important;
    border-left-color: #FFFFFF !important;
  }
</style>
