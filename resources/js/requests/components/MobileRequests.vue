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

export default {
  components: { Card },
  mixins: [datatableMixin],
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
    fetch() {
      Vue.nextTick(() => {
        let pmql = "";

        if (this.pmql !== undefined) {
          pmql = this.pmql;
        }

        let { filter } = this;

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = "";
          }
        }

        if (this.previousFilter !== filter) {
          this.page = 1;
        }

        this.previousFilter = filter;

        if (this.previousPmql !== pmql) {
          this.page = 1;
        }

        this.previousPmql = pmql;

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            `${this.endpoint}?page=${
              this.page
            }&per_page=${
              this.perPage
            }&include=process,participants,data`
                  + `&pmql=${
                    encodeURIComponent(pmql)
                  }&filter=${
                    filter
                  }&order_by=${
                    this.orderBy === "__slot:ids" ? "id" : this.orderBy
                  }&order_direction=${
                    this.orderDirection
                  }${this.additionalParams}`,
          )
          .then((response) => {
            this.data = this.transform(response.data);
          }).catch((error) => {
            if (_.has(error, "response.data.message")) {
              ProcessMaker.alert(error.response.data.message, "danger");
            } else if (!(_.has(error, "response.data.error"))) {
              throw error;
            }
          });
      });
    },
  },
};
</script>
