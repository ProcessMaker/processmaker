<template>
  <div class="data-table">
    <div>
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
        single="Screen"
        plural="Screens"
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
import ScreenDetail from "./screenDetail.vue";

Vue.component("ScreenDetail", ScreenDetail);

export default {
  mixins: [datatableMixin],
  props: ["id", "information", "permission", "screens"],
  data() {
    return {
      orderBy: "title",
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

  methods: {
    preview(data) {
      window.open(`/requests/${this.id}/task/${data.id}/screen/${data.screen_id}`);
    },
    previewScreen(data) {
      data.view = !data.view;
      this.$refs.screens.toggleDetailRow(data.id);
    },
    fetch() {
      this.screens.forEach((item) => {
        item.view = false;
        return item;
      });
      this.data = this.screens;
    },
  },
};
</script>

<style lang="scss" scoped>
</style>
