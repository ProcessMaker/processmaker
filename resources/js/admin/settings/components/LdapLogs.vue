<template>
  <div class="data-table">
    <data-loading
      :for="/logs\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader"  class="card card-body table-card">
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
        pagination-path="meta"
      >
        <template slot="message" slot-scope="props">
            <ul>
                <li v-for="(item, key) in props.rowData.message" :key="key">
                    {{ key + ": " + item }}
                </li>
            </ul>
        </template>
      </vuetable>
      <pagination
        :single="$t('Log')"
        :plural="$t('Logs')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>


<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["filter", "permission"],
  data() {
    return {
      localLoadOnStart: true,
      orderBy: "created_at",
      data: [],
      // Our listing of logs
      sortOrder: [
        {
          field: "created_at",
          sortField: "created_at",
          direction: "desc"
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id"
        },
        {
          title: () => this.$t("Tag"),
          name: "tag",
          sortField: "tag"
        },
        {
          title: () => this.$t("Service"),
          name: "service",
          sortField: "service"
        },
        {
          title: () => this.$t("Message"),
          name: "__slot:message",
          field: "message"
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        }
      ]
    };
  },
  created() {
      ProcessMaker.EventBus.$on("api-data-logs", (val) => {
        this.localLoadOnStart = val;
        this.fetch();
        this.apiDataLoading = false;
        this.apiNoResults = false;
      });
  },
  methods: {
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "logs?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
</style>
