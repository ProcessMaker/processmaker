<template>
  <div>
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
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
      fields: [],
      previousFilter: "",
      previousPmql: "",
      endpoint: "requests",
    };
  },
  methods: {
  },
};
</script>
