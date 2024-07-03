<template>
  <div>
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        :fields="fields"
        type="requests"
      />
    </template>
    <!-- Improve pagination for cards -->
    <pagination
      ref="pagination"
      :single="$t('Request')"
      :plural="$t('Requests')"
      :per-page-select-enabled="true"
      @changePerPage="changePerPage"
      @vuetable-pagination:change-page="onPageChange"
    />
  </div>
</template>

<script>
import Card from "../../Mobile/Card.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import ListMixin from "./ListMixin";

export default {
  components: { Card },
  mixins: [datatableMixin, ListMixin],
  data() {
    return {
      data: "",
      pmql: "",
      filter: "",
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc",
        },
      ],
      fields: [
        {
          label: "Process",
          field: "name",
        },
        {
          label: "Task",
          field: "tasks",
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "dateTime",
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "dateTime",
        },
      ],
      previousFilter: "",
      previousPmql: "",
      endpoint: "requests",
    };
  },
  mounted() {
    this.pmql = `(status = "In Progress") AND (requester = "${Processmaker.user.username}")`;
  },
  methods: {
    updatePmql(value, status) {
      if (!status) {
        this.pmql = `(status = "In Progress") ${value}`;
        return;
      }
      this.pmql = value.substr(3);
    },
  },
};
</script>
