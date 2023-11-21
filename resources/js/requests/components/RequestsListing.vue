<template>
  <div>
    <data-loading
      v-show="shouldShowLoader"
      :for="/requests\?page|results\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
    >
      <filter-table
        :headers="tableHeaders"
        :data="data"
        @table-elipsis-click="handleElipsisClick"
        @table-row-click="handleRowClick"
      />
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import moment from "moment";
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading.js";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import ListMixin from "./ListMixin";
import { FilterTable } from "../../components/shared";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ListMixin],
  props: {
    filter: {},
    columns: {},
    pmql: {},
    savedSearch: {
      default: false,
    },
  },
  data() {
    return {
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc",
        },
      ],
      fields: [],
      previousFilter: "",
      previousPmql: "",
      tableHeaders: [],
    };
  },
  computed: {
    endpoint() {
      if (this.savedSearch !== false) {
        return `saved-searches/${this.savedSearch}/results`;
      }

      return "requests";
    },
  },
  mounted() {
    this.setupColumns();
  },
  methods: {
    setupColumns() {
      const columns = this.getColumns();
      this.tableHeaders = this.getColumns();

      columns.forEach((column) => {
        const field = {
          title: () => this.$t(column.label),
        };

        switch (column.field) {
          case "id":
            field.name = "__slot:ids";
            field.title = "#";
            break;
          case "participants":
            field.name = "__slot:participants";
            break;
          case "name":
            field.name = "__slot:name";
            break;
          default:
            field.name = column.field;
        }

        if (!field.field) {
          field.field = column.field;
        }

        if (column.format === "datetime") {
          field.callback = "formatDateUser|datetime";
        }

        if (column.format === "date") {
          field.callback = "formatDateUser|date";
        }

        if (column.sortable === true && !field.sortField) {
          field.sortField = column.field;
        }

        this.fields.push(field);
      });

      this.fields.push({
        name: "__slot:actions",
        title: "",
      });

      // this is needed because fields in vuetable2 are not reactive
      // this.$nextTick(() => {
      //   this.$refs.vuetable.normalizeFields();
      // });
    },
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      }
      return [
        {
          label: "#",
          field: "id",
          sortable: true,
          default: true,
          width: 45,
        },
        {
          label: "Name",
          field: "name",
          sortable: true,
          default: true,
          width: 140,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 190,
        },
        {
          label: "Participants",
          field: "participants",
          sortable: false,
          default: true,
          width: 160,
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 220,
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 220,
        },
      ];
    },
    openRequest(data, index) {
      return `/requests/${data.id}`;
    },
    formatStatus(status) {
      let color = "success",
        label = "In Progress";
      switch (status) {
        case "DRAFT":
          color = "danger";
          label = "Draft";
          break;
        case "CANCELED":
          color = "danger";
          label = "Canceled";
          break;
        case "COMPLETED":
          color = "primary";
          label = "Completed";
          break;
        case "ERROR":
          color = "danger";
          label = "Error";
          break;
      }
      return (
        '<span class="badge badge-' +
        color +
        ' status-' +
        color +
        '">' +
        this.$t(label) +
        "</span>"
      );
    },
    formatParticipants(participants) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": participants,
          "hide-name": true,
        },
      };
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);
      for (let record of data.data) {
        //format Status
        record["status"] = this.formatStatus(record["status"]);
        record["participants"] = this.formatParticipants(record["participants"]);
      }
      return data;
    },
    fetch() {
      Vue.nextTick(() => {
        if (this.cancelToken) {
          this.cancelToken();
          this.cancelToken = null;
        }

        const CancelToken = ProcessMaker.apiClient.CancelToken;

        let pmql = '';

        if (this.pmql !== undefined) {
          pmql = this.pmql;
        }

        let filter = this.filter;

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = '';
          }
        }

        if (this.previousFilter !== filter) {
          this.page = 1;
        }

        this.previousFilter = filter;

        if (this.previousPmql !== pmql) {
          this.page = 1;
        }

        this.previousPmql = pmql;

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            `${this.endpoint}?page=` +
            this.page +
            "&per_page=" +
            this.perPage +
            "&include=process,participants,data" +
            "&pmql=" +
            encodeURIComponent(pmql) +
            "&filter=" +
            filter +
            "&order_by=" +
            (this.orderBy === "__slot:ids" ? "id" : this.orderBy) +
            "&order_direction=" +
            this.orderDirection +
            this.additionalParams,
            {
              cancelToken: new CancelToken((c) => {
                this.cancelToken = c;
              }),
            },
          )
          .then((response) => {
            this.data = this.transform(response.data);
          }).catch((error) => {
            if (error.code === "ERR_CANCELED") {
              return;
            }
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
    handleElipsisClick(event) {
      console.log(event);
    },
    handleRowClick(row) {
      window.location.href = this.openRequest(row, 1);
    },
  },
};
</script>
<style>
</style>
