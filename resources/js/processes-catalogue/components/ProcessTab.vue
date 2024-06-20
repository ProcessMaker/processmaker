<template>
  <div class="tab-style">
    <b-tabs
      id="tabs-custom"
      pills
      lazy
      @input="onTabChanged"
      >
      <b-tab
        :title="$t('My Cases')"
        active
        >
        <requests-listing
          ref="requestList"
          :filter="filterRequest"
          :columns="columnsRequest"
          :pmql="fullPmqlRequest"
          :autosaveFilter="false"
          ></requests-listing>
      </b-tab>
      <b-tab
        :title="$t('My Tasks')"
        >
        <tasks-list
          ref="taskList"
          :filter="filterTask"
          :pmql="fullPmqlTask" 
          :columns="columnsTask"
          :disable-tooltip="false"
          :disable-quick-fill-tooltip="false"
          :fetch-on-created="false"
          :autosaveFilter="false"
          ></tasks-list>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import RequestTab from "./RequestTab.vue";
import TaskTab from "./TaskTab.vue";
import RequestsListing from "../../requests/components/RequestsListing.vue";
import TasksList from "../../tasks/components/TasksList.vue";
export default {
  components: {
    RequestTab,
    TaskTab,
      RequestsListing,
      TasksList
  },
  props: {
    currentUser: {
      type: Object,
    },
    process: {
      type: Object,
    },
  },
  data() {
    return {
      filterRequest: "",
      fullPmqlRequest: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
      columnsRequest: [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 100,
          filter_subject: {type: 'Status'}
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160
        },
      ],
      filterTask: "",
      fullPmqlTask: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
      columnsTask: window.Processmaker.defaultColumns || null
    };
  },
  methods: {
    onTabChanged(activeTabIndex) {
      if (activeTabIndex === 1) {
        this.$nextTick(() => {
          this.$refs.taskList.fetch();
        });
      }
    }
  }
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
