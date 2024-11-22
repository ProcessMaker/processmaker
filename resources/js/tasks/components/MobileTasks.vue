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
    advancedFilter: {
      type: Object,
      default: () => null,
    },
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
    // Check if advanced filters are provided
    if (this.advancedFilter?.filters) {
      // Set pmql to filter by user_id
      this.pmql = `(user_id = ${ProcessMaker.user.id})`;
    } else {
      // Set pmql to filter by user_id and status, including process filter if available
      this.pmql = `(user_id = ${ProcessMaker.user.id}) AND (status = "In Progress")${filter}`;
    }
  },
  methods: {
    updatePmql(value) {
      this.pmql = `(user_id = ${ProcessMaker.user.id}) ${value}`;
    },
    updateOrder(value) {
      this.sortOrder[0].sortField = value;
    },
    /**
     * Generates the advanced filter query string for the API request.
     * If advanced filters are provided, it formats them by removing keys that start with an underscore
     * and then encodes them as a query string.
     *
     * @returns {string} The encoded advanced filter query string or an empty string if no filters are provided.
     */
    getAdvancedFilter() {
      if (this.advancedFilter?.filters) {
        this.pmql = '';

        // Format the filters by removing keys that start with an underscore
        let formattedFilter = this.advancedFilter.filters.map(obj =>
          Object.fromEntries(Object.entries(obj).filter(([key, _]) => !key.startsWith('_')))
        );

        // Encode the formatted filters as a query string
        return "&advanced_filter=" + encodeURIComponent(JSON.stringify(formattedFilter));
      }
      
      // Return an empty string if no filters are provided
      return "";
    },
  },
};
</script>
