<template>
  <div>
    <b-row>
      <b-col cols="10">
        <pmql-input
          class="mb-2"
          :search-type="'security_logs'"
          :value="query"
          :ai-enabled="false"
          :aria-label="$t('Advanced Search (PMQL)')"
          @submit="onNLQConversion"
        />
      </b-col>
      <b-col cols="2">
        <div>
          <b-button
            id="downloadLogUser"
            variant="primary"
            @click="prepareDownloadLogs"
          >
            {{ $t('CSV') }}
          </b-button>
          <b-button
            id="downloadLogUser"
            variant="primary"
            @click="downloadXml"
          >
            {{ $t('XML') }}
          </b-button>
        </div>
      </b-col>
    </b-row>
    <div class="data-table">
      <div class="card card-body table-card">
        <vuetable
          :data-manager="dataManager"
          :sort-order="sortOrder"
          :css="css"
          :api-mode="false"
          :fields="fields"
          :data="data"
          data-path="data"
          :no-data-template="$t('No Data Available')"
          pagination-path="meta"
          @vuetable:pagination-data="onPaginationData"
        >
          <template
            slot="event"
            slot-scope="props"
          >
            <span class="text-capitalize font-weight-bold">{{ props.rowData.event }}</span>
          </template>
          <template
            slot="browser"
            slot-scope="props"
          >
            <span v-if="props.rowData.meta.browser.name">
              {{ props.rowData.meta.browser.name }}
            </span>
            <span v-else>
              {{ $t('Unidentified') }}
            </span>
          </template>
          <template
            slot="os"
            slot-scope="props"
          >
            <span v-if="props.rowData.meta.os.name">
              {{ props.rowData.meta.os.name }}
            </span>
            <span v-else>
              {{ $t('Unidentified') }}
            </span>
          </template>
          <template
            slot="actions"
            slot-scope="props"
          >
            <span>
              <b-button
                variant="outline-primary"
                :disabled="props.rowData.event === 'login' || props.rowData.event === 'logout' || props.rowData.event === 'attempt'"
                @click="showLogInfo(props)"
              >
                <i class="fa fa-play" />
              </b-button>
            </span>
          </template>
        </vuetable>
        <pagination
          ref="pagination"
          :single="$t('Logged Event')"
          :plural="$t('Logged Events')"
          :per-page-select-enabled="true"
          @changePerPage="changePerPage"
          @vuetable-pagination:change-page="onPageChange"
        />
      </div>
    </div>
    <SecurityLogsModal ref="modal-logs" />
  </div>
</template>

<script>
import { BasicSearch } from "SharedComponents";
import datatableMixin from "../../../components/common/mixins/datatable";
import isPMQL from "../../../modules/isPMQL";
import PmqlInput from "../../../components/shared/PmqlInput";
import SecurityLogsModal from "./SecurityLogsModal.vue";
import { json2xml } from "xml-js";

export default {
  components: { BasicSearch, PmqlInput, SecurityLogsModal },
  mixins: [datatableMixin],
  props: ["userId"],
  data() {
    return {
      orderBy: "occurred_at",
      orderDirection: "desc",
      sortOrder: [
        {
          field: "occurred_at",
          sortField: "occurred_at",
          direction: "desc",
        },
      ],
      query: "",
      fields: [
        {
          title: () => this.$t("Event"),
          name: "__slot:event",
          sortField: "event",
        },
        {
          title: () => this.$t("IP Address"),
          name: "ip",
          sortField: "ip",
        },
        {
          title: () => this.$t("Browser"),
          name: "__slot:browser",
          sortField: "meta.browser.name",
        },
        {
          title: () => this.$t("Operating System"),
          name: "__slot:os",
          sortField: "meta.os.name",
        },
        {
          title: () => this.$t("Occurred At"),
          name: "occurred_at",
          sortField: "occurred_at",
        },
        {
          name: "__slot:actions",
        },
      ],
      fileTitle: "security-logs",
    };
  },
  computed: {
    pmql() {
      let pmql = `user_id = ${this.userId}`;

      if (this.query.isPMQL()) {
        pmql += ` AND (${this.query})`;
      }

      return pmql;
    },
    searchFilter() {
      let searchFilter = "";

      if (!this.query.isPMQL()) {
        searchFilter = this.query;
      }

      return searchFilter;
    },
  },
  methods: {
    fetch() {
      this.loading = true;
      ProcessMaker.apiClient
        .get(
          `security-logs?pmql=${
            this.pmql
          }&filter=${
            this.searchFilter
          }&page=${
            this.page
          }&per_page=${
            this.perPage
          }&order_by=${
            this.orderBy
          }&order_direction=${
            this.orderDirection}`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    runSearch() {
      this.fetch();
    },
    onNLQConversion(pmql) {
      this.query = pmql;
      this.runSearch();
    },
    /**
     * Emit to show the modal
     */
    showLogInfo(data) {
      this.$refs["modal-logs"].showLogInfo(data);
    },
    /**
     * Download all user's logs
     */
    prepareDownloadLogs() {
      const headers = {
        id: "id",
        user_id: "user_id",
        event: "event",
        occurred_at: "occurred_at",
        meta: "meta",
        data: "data",
      };
      const itemsNotFormatted = [...this.data.data];
      const itemsFormatted = [];

      // format the data
      itemsNotFormatted.forEach((item) => {
        itemsFormatted.push({
          id: item.id,
          user_id: item.user_id,
          event: item.event,
          occurred_at: item.occurred_at,
          meta: JSON.stringify(item.meta).replace(/,/g, "__").replace(/;/g, "_"),
          data: JSON.stringify(item.data).replace(/,/g, "__"),
        });
      });

      const fileTitle = "security-logs"; // or 'my-unique-title'

      this.exportCSVFile(headers, itemsFormatted);
    },
    /**
     * Export the JSON data to CSV
     */
    exportCSVFile(headers, items) {
      if (headers) {
        items.unshift(headers);
      }

      // Convert Object to JSON
      const jsonObject = JSON.stringify(items);
      const csv = this.convertToCSV(jsonObject);
      const exportedFilenmae = `${this.fileTitle}.csv` || "export.csv";
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });

      if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, exportedFilenmae);
      } else {
        const link = document.createElement("a");
        if (link.download !== undefined) {
          const url = URL.createObjectURL(blob);
          link.setAttribute("href", url);
          link.setAttribute("download", exportedFilenmae);
          link.style.visibility = "hidden";
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }
      }
    },
    /**
     * Convert array to CSV format
     */
    convertToCSV(objArray) {
      const array = typeof objArray !== "object" ? JSON.parse(objArray) : objArray;
      let str = "";
      let i;
      let index;
      let line = "";

      for (i = 0; i < array.length; i += 1) {
        line = "";
        for (index in array[i]) {
          if (line !== "") {
            line += ",";
          }
          line += array[i][index];
        }
        str += `${line}\r\n`;
      }
      return str;
    },
    /**
     * Download the data in a XML file
     */
    downloadXml() {
      const logXml = json2xml(this.data.data, { compact: true });
      const exportedFilenmae = `${this.fileTitle}.xml` || "export.xml";
      const blob = new Blob([logXml], { type: ".xml" });

      if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, exportedFilenmae);
      } else {
        const link = document.createElement("a");
        if (link.download !== undefined) {
          const url = URL.createObjectURL(blob);
          link.setAttribute("href", url);
          link.setAttribute("download", exportedFilenmae);
          link.style.visibility = "hidden";
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }
      }
    },
  },
};
</script>
