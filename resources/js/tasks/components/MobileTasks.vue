<template>
  <div ref="tasksContainer" class="tasks-container">
    <!-- <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        type="tasks"
      />
    </template> -->
    <!-- Improve pagination for cards -->
    <!-- <pagination
      ref="pagination"
      :single="$t('Task')"
      :plural="$t('Tasks')"
      :per-page-select-enabled="true"
      @changePerPage="changePerPage"
      @vuetable-pagination:change-page="onPageChange"
    /> -->
    <template v-for="(item, index) in data.data">
      <card
        :key="index"
        :item="item"
        :show-cards="true"
        type="tasks"
      />
      <!-- <p>itera index: {{ index }}</p> -->
      <div v-if="(index % 15 === 14) && data.data.length >= 15" style="width: 100%;">
        <!-- <p>cumple if 15 perPage: {{ perPage }} index {{ index }} datalength {{data.data.length  }}</p> -->
              <Card v-if="((index + 1) === data.data.length)"
              style="width: 96%;"
              :show-cards="false"
              :current-page="counterPage + Math.floor(index / 15)"
              :total-pages="lastPage"
              :card-message="'show-more'"
              :loading="loading"
            />
            <Card
              v-else
              style="width: 96%;"
              :show-cards="false"
              :current-page="counterPage + Math.floor(index / 15)"
              :total-pages="lastPage"
              :card-message="cardMessage"
              :loading="loading"
            />
            <!-- <div v-if="((index + 1) === data.data.length)">
              <p>Separador: {{ cardMessage }} </p>
            </div>
            <div v-else>
              <p>page of: {{ cardMessage }} </p>
            </div> -->
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
