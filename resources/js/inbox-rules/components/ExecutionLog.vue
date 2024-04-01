<template>
  <div>
    <PMTable
      :headers="columns()"
      :data="response"
      @page-change="changePage"
      :empty="$t('No rules were executed yet')"
      :empty-desc="$t('Once rules start running, you can see the results here.')"
      empty-icon="noData"
      />
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  export default {
    components: {
      PMTable,
    },
    data() {
      return {
        response: {data: [], meta: {}},
        page: 1,
      }
    },
    methods: {
      load() {
        const params = {
          page: this.page
        };
        ProcessMaker.apiClient.get("tasks/rule-execution-log", {params})
                .then(response => {
                  this.response = response.data;
                });
      },
      changePage(page) {
        this.page = page;
        this.load();
      },
      columns() {
        return [
          {
            label: this.$t("Case #"),
            field: "task.process_request.case_number",
          },
          {
            label: this.$t("Case Name"),
            field: "task.process_request.case_title",
          },
          {
            label: this.$t("Run Date"),
            field: "created_at",
            format: "datetime",
          },
          {
            label: this.$t("Applied Rule"),
            field: "inbox_rule_attributes.name",
          },
          {
            label: this.$t("Task Due Date"),
            field: "task.due_at",
            format: "datetime",
          },
          {
            label: this.$t("Task Name"),
            field: "task.element_name",
          },
          {
            label: this.$t("Status"),
            field: "task.status",
          }
        ]
      }
    }
  }
</script>