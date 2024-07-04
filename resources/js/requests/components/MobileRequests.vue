<template>
  <div ref="requestsContainer" class="requests-container">
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        :show-cards="true"
        type="requests"
      />
      <div v-if="(index % perPage === perPage - 1) && data.data.length >= perPage" style="width: 100%;">
              <Card v-if="((index + 1) === data.data.length)"
              :show-cards="false"
              :current-page="counterPage + Math.floor(index / perPage)"
              :total-pages="totalPages"
              :card-message="'show-more'"
              :loading="loading"
            />
            <Card
              v-else
              :show-cards="false"
              :current-page="counterPage + Math.floor(index / perPage)"
              :total-pages="totalPages"
              :card-message="cardMessage"
              :loading="loading"
            />
      </div>
    </template>
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
      fields: [],
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
