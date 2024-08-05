<template>
  <div ref="requestsContainer" class="requests-container">
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
        type="requests"
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
  props:{
    process: Object,
  },
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
    this.pmql = `(status = "In Progress") AND (requester = "${Processmaker.user.username}") AND (process_id = ${this.process.id})`;
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
