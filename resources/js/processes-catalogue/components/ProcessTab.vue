<template>
  <div class="tab-style">
    <b-tabs
      id="tabs-custom"
      pills
      lazy
    >
      <b-tab
        :title="$t('My Requests')"
        active
      >
        <request-tab
          :currentUser="currentUser"
          :process="process"
        ></request-tab>
      </b-tab>
      <b-tab
        :title="$t('My Tasks')"
      >
        <task-tab
          :currentUser="currentUser"
          :process="process"
        ></task-tab>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import RequestTab from "./RequestTab.vue";
import TaskTab from "./TaskTab.vue";

export default {
  components: {
    RequestTab,
    TaskTab,
  },
  props: {
    currentUser: {
      type: Object,
    },
    process: {
      type: Object,
    },
  },
  mounted() {
    sessionStorage.setItem("elementDestinationURL", window.location.href);
  },
};
</script>
<style>
.tab-style {
  background-color: white;
  padding-top: 32px;
}
.nav-pills .nav-link.active {
  background-color: white;
  color: #6a7888;
  box-shadow: 1px 1px 3px 0px #0000001A;
  border-radius: 8px;
}
.nav-pills .nav-link {
  width: 239px;
  color: #6a7888;
  background-color: #dee3e9;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: -0.02em;
  text-align: center;
}
#tabs-custom ul {
  background-color: #dee3e9;
  padding: 3px;
  margin-bottom: 32px;
  width: fit-content;
  border-radius: 10px;
}
#tabs-custom div:has(ul) {
  display: flex;
  justify-content: center;
}
div:has(.tab-pane) {
  width: 100%;
}
</style>
