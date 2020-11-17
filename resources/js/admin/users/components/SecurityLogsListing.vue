<template>
  <div>
    <basic-search class="mb-3" v-model="query" @submit="runSearch"></basic-search>
    <div class="data-table">
      <div class="card card-body table-card">
        <vuetable
         :dataManager="dataManager"
         :sortOrder="sortOrder"
         :css="css"
         :api-mode="false"
         @vuetable:pagination-data="onPaginationData"
         :fields="fields"
         :data="data"
         data-path="data"
         :noDataTemplate="$t('No Data Available')"
         pagination-path="meta">
          <template slot="event" slot-scope="props">
            <span class="text-capitalize font-weight-bold">{{ props.rowData.event }}</span>
          </template>
          <template slot="browser" slot-scope="props">
            <span v-if="props.rowData.meta.browser.name">
              {{ props.rowData.meta.browser.name }}
            </span>
            <span v-else>
              {{ $t('Unidentified') }}
            </span>
          </template>
          <template slot="os" slot-scope="props">
            <span v-if="props.rowData.meta.os.name">
              {{ props.rowData.meta.os.name }}
            </span>
            <span v-else>
              {{ $t('Unidentified') }}
            </span>
          </template>
        </vuetable>
        <pagination
                :single="$t('Logged Event')"
                :plural="$t('Logged Events')"
                :perPageSelectEnabled="true"
                @changePerPage="changePerPage"
                @vuetable-pagination:change-page="onPageChange"
                ref="pagination"
        ></pagination>
      </div>
    </div>
  </div>
</template>

<script>
  import datatableMixin from "../../../components/common/mixins/datatable";
  import { BasicSearch } from "SharedComponents";
  import isPMQL from "../../../modules/isPMQL";

  export default {
    components: { BasicSearch },
    mixins: [datatableMixin],
    props: ["userId"],
    computed: {
      pmql: function() {
        let pmql = `user_id = ${this.userId}`;
        
        if (this.query.isPMQL()) {
          pmql += ` AND (${this.query})`;
        }
        
        return pmql;
      },
      searchFilter: function() {
        let searchFilter = '';
        
        if (!this.query.isPMQL()) {
          searchFilter = this.query;
        }
        
        return searchFilter;
      }
    },
    data() {
      return {
        orderBy: "occurred_at",
        orderDirection: "desc",
        sortOrder: [
          {
            field: "occurred_at",
            sortField: "occurred_at",
            direction: "desc"
          }
        ],
        query: '',
        fields: [
          {
            title: () => this.$t("Event"),
            name: "__slot:event",
            sortField: "event"
          },
          {
            title: () => this.$t("IP Address"),
            name: "ip",
            sortField: "ip"
          },
          {
            title: () => this.$t("Browser"),
            name: "__slot:browser",
            sortField: "meta.browser.name"
          },
          {
            title: () => this.$t("Operating System"),
            name: "__slot:os",
            sortField: "meta.os.name"
          },
          {
            title: () => this.$t("Occurred At"),
            name: "occurred_at",
            sortField: "occurred_at"
          }
        ]
      };
    },
    methods: {
      fetch() {
        this.loading = true;
        ProcessMaker.apiClient
          .get(
            "security-logs?pmql=" +
            this.pmql +
            "&filter=" +
            this.searchFilter +
            "&page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
          )
          .then(response => {
            this.data = this.transform(response.data);
            this.loading = false;
          });
      },
      runSearch() {
        this.fetch();
      }
    }
  }

</script>
