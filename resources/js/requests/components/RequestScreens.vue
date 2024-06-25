<template>
  <div class="data-table">
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
      :single="$t('Screen')"
      :plural="$t('Screens')"
      :perPageSelectEnabled="true"
      @changePerPage="changePerPage"
      @vuetable-pagination:change-page="onPageChange"
      ref="pagination">
    </pagination>
  </div>

</template>

<script>
  import Vue from "vue";
  import datatableMixin from "../../components/common/mixins/datatable";
  import ScreenDetail from '../components/screenDetail';

  Vue.component('screen-detail', ScreenDetail);

  export default {
    mixins: [datatableMixin],
    props: ["id", "information", "permission"],
    data() {
      return {
        orderBy: "completed_at",
        screens: [],
        filter: '',
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
            title: ""
          }
        ]
      };
    },
    mounted() {
      this.fetch();
    },
    methods: {
      preview(data) {
        window.open('/requests/' + this.id + '/task/' + data.id + '/screen/' + data.screen_id);
      },
      previewScreen(data) {
        data.view = !data.view;
        this.$refs.screens.toggleDetailRow(data.id);
      },
      fetch() {
        Vue.nextTick(() => {
          let endpoint = `/requests/${this.id}/details-screen-request`;
          // Load from our api client
          ProcessMaker.apiClient
            .get(
              `${endpoint}?page=` +
              this.page +
              "&per_page=" +
              this.perPage +
              "&filter=" +
              this.filter +
              "&order_by=" +
              this.orderBy +
              "&order_direction=asc" 
            )
            .then((response) => {
              this.data = this.transform(response.data);
              this.screens = this.data.data;
              this.screens.forEach(item => {
                item.view = false;
                return item;
              });
              
            }).catch((error) => {
              this.data = [];
              if (_.has(error, 'response.data.message')) {
                ProcessMaker.alert(error.response.data.message, 'danger');
              } else if (_.has(error, 'response.data.error')) {
                return;
              } else {
                throw error;
              }
            });
        });
      },
    },
  };
</script>

<style lang="scss" scoped>
</style>
