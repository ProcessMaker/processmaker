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
        detail-row-component="detail-header"
        @vuetable:cell-clicked="detail"
        ref="headers"
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
  import DetailHeader from "../components/DetailHeader";

  Vue.component("detail-header", DetailHeader);

  export default {
    mixins: [datatableMixin],
    props: {
      filter: {
        type: String,
        default: ""
      },
      headers: {
        type: Array,
        default: []
      }
    },
    data () {
      return {
        orderBy: "key",
        sortOrder: [
          {
            field: "key",
            sortField: "key",
            direction: "asc"
          }
        ],
        fields: [
          {
            title: () => this.$t("Key"),
            name: "key",
            sortField: "key"
          },
          {
            title: () => this.$t("Value"),
            name: "value",
            sortField: "value"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    methods: {
      fetch () {
        this.data = [];
        if (this.headers) {
          let index = 0;
          this.data = this.headers.map(item => {
            item.view = false;
            item.id = index;
            index++;
            return item;
          });
        }
      },
      detail (data) {
        data.view = !data.view;
        this.$refs.headers.toggleDetailRow(data.id);
      },
      doDelete (item) {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          "<b>" +
          this.$t("Are you sure you want to delete {{item}}?", {
            item: item.key
          }) +
          "</b>",
          "",
          () => {
            for (let i = 0; i < this.headers.length; i++) {
              if (this.headers[i].id === item.id) {
                this.headers.splice(i, 1);
              }
            }
            this.fetch();
          }
        );
      }
    }
  };
</script>
