<template>
  <div ref="tasksContainer" class="tasks-container">
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        type="tasks"
      />
    </template>
    <!-- Improve pagination for cards -->
    <pagination
      ref="pagination"
      :single="$t('Task')"
      :plural="$t('Tasks')"
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
  props: {
    filter: {},
  },
  data() {
    return {
      data: "",
      pmql: "",
      orderBy: "ID",
      order_direction: "DESC",
      status: "",
      sortOrder: [
        {
          field: "ID",
          sortField: "ID",
          direction: "DESC",
        },
      ],
      fields: [],
      previousFilter: "",
      previousPmql: "",
      endpoint: "tasks",
    };
  },
  mounted() {
    this.pmql = `(user_id = ${ProcessMaker.user.id}) AND (status = "In Progress")`;    
  },
  methods: {
    updatePmql(value) {
      this.pmql = `(user_id = ${ProcessMaker.user.id}) ${value}`;
    },
    updateOrder(value) {
      this.sortOrder[0].sortField = value;
    },
  },
};
</script>
<style scoped>
.tasks-container {
  /*overflow: auto;*/
}
</style>