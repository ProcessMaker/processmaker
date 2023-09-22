<template>
  <div>
    <filter-mobile type="tasks" />
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
import FilterMobile from "../../Mobile/FilterMobile.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import ListMixin from "./ListMixin";

export default {
  components: { Card, FilterMobile },
  mixins: [datatableMixin, ListMixin],
  data() {
    return {
      data: "",
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
  methods: {
  },
};
</script>
