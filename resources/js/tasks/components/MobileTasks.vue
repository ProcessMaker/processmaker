<template>
  <div ref="tasksContainer" class="tasks-container">
    <PMMessageResults :baseURL="endpoint"
                      :shouldShowLoader="shouldShowLoader"
                      :dataLoadingId="dataLoadingId"
                      :message="$t('No items to show')"
                      :description="$t('You have to start a Case of this process.')">
    </PMMessageResults>
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        :fields="fields"
        :show-cards="true"
        type="tasks"
        />
      <mobile-cards-pagination
        :index="index"
        :per-page="perPage"
        :data-length="data.data.length"
        :counter-page="counterPage"
        :total-pages="totalPages"
        :card-message="cardMessage"
        :loading="loading"
        />
    </template>
  </div>
</template>
<script>
import Card from "../../Mobile/Card.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import ListMixin from "./ListMixin";
import MobileCardsPagination from "../../Mobile/MobileCardsPagination.vue";
import PMMessageResults from "../../components/PMMessageResults.vue";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
export default {
  components: {Card, MobileCardsPagination, PMMessageResults},
  mixins: [datatableMixin, ListMixin, dataLoadingMixin],
  props: {
    filter: {},
    process: Object,
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
      fields: [
        {
          label: "Task",
          field: "element_name",
        },
        {
          label: "Due",
          field: "due_at",
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
      endpoint: "tasks",
    };
  },
  mounted() {
    const filter = this.process ? ` AND (process_id = ${this.process.id})` : "";
    this.pmql = `(user_id = ${ProcessMaker.user.id}) AND (status = "In Progress")${filter}`;
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
