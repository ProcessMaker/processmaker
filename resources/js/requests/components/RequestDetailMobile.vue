<template>
  <div id="tasksList">
    <template v-for="(item, index) in tasksList">
      <card
        :key="index"
        :item="item"
        :fields="fields"
        :show-cards="true"
        type="tasks"
      />
    </template>
  </div>
</template>

<script>
import datatableMixin from "../../components/common/mixins/datatable";
import Card from "../../Mobile/Card.vue";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";

export default {
  components: { Card },
  mixins: [datatableMixin,  dataLoadingMixin],
  props: ["processRequestId", "isAdmin", "isProcessManager"],
  data() {
    return {
      tasksList: [],
      sortOrder: [
        {
          field: "due_at",
          sortField: "due_at",
          direction: "asc",
        },
      ],
      fields: [
        {
          label: "Task",
          field: "element_name",
        },
        {
          label: "Due Date",
          field: "due_at",
          format: "dateTime",
        },
      ],
    };
  },
  methods: {
    formatDueDate(value, status) {
      let color = "text-dark";
      if (status === "overdue") {
        color = "badge badge-danger";
      }

      return (
        `<span class="${color}">${this.formatDate(value)}</span>`
      );
    },
    transform(list) {
      // Clean up fields for meta pagination so vue table pagination can understand
      list.meta.last_page = list.meta.total_pages;
      list.meta.from = (list.meta.current_page - 1) * list.meta.per_page;
      list.meta.to = list.meta.from + list.meta.count;
      //load data for participants
      for (let record of list.data) {
        record["participants"] = record["user"] ? [record["user"]] : [];

        let color = "text-primary";
        if (record["status"] === "overdue") {
          color = "badge badge-danger";
        }

        record["due_at"] = this.formatDueDate(
          record["due_at"],
          record["status"]
        );
      }
      return list;
    },
    fetch() {
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "tasks?process_request_id=" +
          this.processRequestId +
          "&status=ACTIVE"
        )
        .then((response) => {
          response.data.data.forEach((item) => {
            item.process = item.process_request;
            this.tasksList.push(item);
          });
          this.loading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
:deep(tr td:nth-child(3)) {
  padding: 6px 10px;
}
</style>
