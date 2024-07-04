<template>
  <div ref="tasksContainer" class="tasks-container">
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        :show-cards="true"
        type="tasks"
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
