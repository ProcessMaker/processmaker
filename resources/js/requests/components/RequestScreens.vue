<template>
  <div class="data-table">
    <div>
      <vuetable
        :dataManager="dataManager"
        :noDataTemplate="$t('No Data Available')"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        detail-row-component="screen-detail"
        @vuetable:cell-clicked="previewScreen"
        ref="screens"
        pagination-path="meta">

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="previewScreen(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Details')">
                <i v-if="!props.rowData.view" class="fas fa-search-plus fa-lg fa-fw"></i>
                <i v-else class="fas fa-search-minus fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="preview(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Print')">
                <i class="fas fa-print fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        single="Screen"
        plural="Screens"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination">
      </pagination>
    </div>
  </div>
</template>

<script>
  import Vue from "vue";
  import datatableMixin from "../../components/common/mixins/datatable";
  import ScreenDetail from '../components/screenDetail';

  Vue.component('screen-detail', ScreenDetail);

  export default {
    mixins: [datatableMixin],
    props: ["id", "information", "permission", "screens"],
    data() {
      return {
        orderBy: "title",
        dupScreen: {
          title: "",
          description: ""
        },
        errors: [],
        sortOrder: [
          {
            field: "title",
            sortField: "title",
            direction: "asc"
          }
        ],

        fields: [
          {
            title: this.$t("Screen"),
            name: "title",
            field: "title",
          },
          {
            title: this.$t("Description"),
            name: "description",
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },

    methods: {
      preview(data) {
        window.open('/requests/' + this.id + '/screen/' + data.screen_id);
      },
      previewScreen(data) {
        data.view = !data.view;
        console.log(data);
        this.$refs.screens.toggleDetailRow(data.id);
      },
      fetch() {
        this.screens.forEach(item => {
          item.view = false;
          return item;
        });
        this.data = this.screens;
      }
    },
  };
</script>

<style lang="scss" scoped>
</style>
