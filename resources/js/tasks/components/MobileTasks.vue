<template>
  <div>
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
// BEGIN-NOSCAN
export default {
  components: { Card },
  mixins: [datatableMixin],
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
        let filterParams = "";

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = "";
          } else {
            filterParams = `&user_id=${
              window.ProcessMaker.user.id
            }&filter=${
              filter
            }&statusfilter=ACTIVE,CLOSED`;
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
            `tasks?page=${
              this.page
            }&include=process,processRequest,processRequest.user,user,data`
              + `&pmql=${
                encodeURIComponent(pmql)
              }&per_page=${
                this.perPage
              }${filterParams
              }${this.getSortParam()
              }&non_system=true`,
          )
          .then((response) => {
            this.data = this.transform(response.data);
            this.$emit("in-overdue", response.data.meta.in_overdue);
          })
          .catch((error) => {
            window.ProcessMaker.alert(error.response.data.message, "danger");
            this.data = [];
          });
      });
    },
    getSortParam() {
      if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
        return (
          `&order_by=${
            this.sortOrder[0].sortField
          }&order_direction=${
            this.sortOrder[0].direction}`
        );
      }
      return "";
    },
  },
};
// END-NOSCAN
</script>
