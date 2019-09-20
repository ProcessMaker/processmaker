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
        detail-row-component="test-end-point-details"
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
                :title="$t('Details')"
              >
                <i v-if="!props.rowData.view" class="fas fa-search-plus fa-lg fa-fw"></i>
                <i v-else class="fas fa-search-minus fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="test(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Test')"
              >
                <i class="fas fa-play fa-lg fa-fw"></i>
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
import TestEndPointDetails from "../components/TestEndPointDetails";

Vue.component("test-end-point-details", TestEndPointDetails);

export default {
  mixins: [datatableMixin],
  props: {
    filter: {
      type: String,
      default: ""
    },
    endpoints: {
      type: Object,
      default: {}
    },
    datasource: {
      type: Object,
      default: {}
    }
  },
  data() {
    return {
      orderBy: "method",
      selected: null,
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
    test(data) {
      window.ProcessMaker.apiClient.post(
        "/datasources/${this.datasource.id}/test"
      );
    },
    fetch() {
      this.data = [];
      if (this.endpoints) {
        let index = 0;
        for (let name in this.endpoints) {
          let item = this.endpoints[name];
          item.view = false;
          item.id = index;
          index++;
          this.data.push(item);
        }
      }
    },
    detail(data) {
      data.view = !data.view;
      this.$refs.endpoints.toggleDetailRow(data.id);
    }
  }
};
</script>
