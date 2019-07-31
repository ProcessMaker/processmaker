<template>
  <div class="data-table">
    <div class="card card-body table-card">
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
        @vuetable:cell-clicked="onCellClicked"
        ref="screens"
        pagination-path="meta">

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onCellClicked(props.rowData, props.rowData)"
                v-b-tooltip.hover
                :title="$t('Detail')">
                <i class="fas fa-search-plus fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="pdf(props.rowData, props.rowData)"
                v-b-tooltip.hover
                :title="$t('PDF')">
                <i class="fas fa-file-pdf fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="print(props.rowData, props.rowData)"
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
    props: ["permission", "information"],
    data() {
      return {
        orderBy: "title",
        dupScreen: {
          title: "",
          type: "",
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
            title: this.$t("Name"),
            name: "title",
            field: "title",
            sortField: "title"
          },
          {
            title: this.$t("Description"),
            name: "description",
            sortField: "description"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },

    methods: {
      onCellClicked(data, field, event) {
        console.log('cellClicked: ', field.title);
        this.$refs.screens.toggleDetailRow(data.id)
      },
      transform(data) {
        /*for (let record of data.data) {
          record['data'] = this.information;
        }*/
        data.data.forEach( item => {
          item.data = this.information;
          return item;
        });
        return data;
      },
      fetch() {
        this.loading = true;
        // Load from our api client
        ProcessMaker.apiClient
          .get(
            "screens" +
            "?page=" +
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
      }
    },

    computed: {}
  };
</script>

<style lang="scss" scoped>
</style>
