<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/details-screen-request\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body scripts-table-card"
      data-cy="screen-requested-table"
    >
      <vuetable
        :data-manager="dataManager"
        :no-data-template="$t('No Data Available')"
        :sort-order="sortOrder"
        :css="css"
        ref="screens"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        detail-row-component="screen-detail"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
        @vuetable:cell-clicked="previewScreen"
      >
        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-b-tooltip.hover
                variant="link"
                :title="$t('Details')"
                @click="previewScreen(props.rowData)"
              >
                <i
                  v-if="!props.rowData.view"
                  class="fas fa-search-plus fa-lg fa-fw"
                />
                <i
                  v-else
                  class="fas fa-search-minus fa-lg fa-fw"
                />
              </b-btn>
              <b-btn
                v-b-tooltip.hover
                variant="link"
                :title="$t('Print')"
                @click="preview(props.rowData)"
              >
                <i class="fas fa-print fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Screen')"
        :plural="$t('Screens')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import ScreenDetail from "../components/screenDetail";

Vue.component("ScreenDetail", ScreenDetail);

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["id", "information", "permission"],
  data() {
    return {
      orderBy: "completed_at",
      screens: [],
      filter: "",
      dupScreen: {
        title: "",
        description: "",
      },
      errors: [],
      sortOrder: [
        {
          field: "title",
          sortField: "title",
          direction: "asc",
        },
      ],

      fields: [
        {
          title: () => this.$t("Screen"),
          name: "title",
          field: "title",
        },
        {
          title: () => this.$t("Description"),
          name: "description",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    preview(data) {
      window.open(
        `/requests/${
          this.id
        }/task/${
          data.id
        }/screen/${
          data.screen_id}`,
      );
    },
    previewScreen(data) {
      data.view = !data.view;
      this.$refs.screens.toggleDetailRow(data.id);
    },
    fetch() {
      this.loading = true;
      const endpoint = `/requests/${this.id}/details-screen-request`;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `${endpoint}?page=${
            this.page
          }&per_page=${
            this.perPage
          }&filter=${
            this.filter
          }&order_by=${
            this.orderBy
          }&order_direction=asc`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.screens = this.data.data;
          this.screens.forEach((item) => {
            item.view = false;
            return item;
          });
          this.loading = false;
        })
        .catch((error) => {
          this.data = [];
          this.loading = false;
          if (_.has(error, "response.data.message")) {
            ProcessMaker.alert(error.response.data.message, "danger");
          } else if (_.has(error, "response.data.error")) {

          } else {
            throw error;
          }
        });
    },
  },
};
</script>

<style lang="scss" scoped>
</style>
