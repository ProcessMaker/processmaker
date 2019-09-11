<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        :noDataTemplate="$t('No Data Available')"
        detail-row-component="detail-end-point"
        @vuetable:cell-clicked="detail"
        ref="endpoints"
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="detail(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Details')">
                <i v-if="!props.rowData.view" class="fas fa-search-plus fa-lg fa-fw"></i>
                <i v-else class="fas fa-search-minus fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doDelete(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Remove')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
    </div>
  </div>
</template>

<script>
  import Vue from "vue";
  import datatableMixin from "../../../components/common/mixins/datatable";
  import DetailEndPoint from "../components/DetailEndPoint";

  Vue.component('detail-end-point', DetailEndPoint);

  export default {
    mixins: [datatableMixin],
    props: ['filter', 'info'],
    data() {
      return {
        orderBy: "method",
        sortOrder: [
          {
            field: "method",
            sortField: "method",
            direction: "asc"
          }
        ],
        fields: [
          {
            title: () => this.$t("Purpose"),
            name: "purpose",
            sortField: "purpose"
          },
          {
            title: () => this.$t("Method"),
            name: "method",
            sortField: "method"
          },
          {
            title: () => this.$t("Url"),
            name: "url",
            sortField: "url"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    methods: {
      fetch() {
          if (this.info) {
            let index = 0;
            this.data = this.info.map(item => {
              item.view = false;
              item.id = index;
              index++;
              return item;
            });
          } else {
            this.data = [];
          }
      },
      detail(data) {
        data.view = !data.view;
        this.$refs.endpoints.toggleDetailRow(data.id);
      },
      doDelete(item) {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          this.$t("Are you sure you want to delete Data Source") + ' ' +
          item.name +
          this.$t("?"),
          "",
          () => {
            //TODO remove item
          }
        );
      }
    }
  };
</script>
