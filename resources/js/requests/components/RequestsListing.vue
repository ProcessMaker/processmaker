<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/requests\?page|results\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body table-card"
    >
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        ref="vuetable"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="ids"
          slot-scope="props"
        >
          <b-link
            class="text-nowrap"
            :href="openRequest(props.rowData, props.rowIndex)"
          >
            #{{ props.rowData.id }}
          </b-link>
        </template>
        <template
          slot="name"
          slot-scope="props"
        >
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.name }}</span>
        </template>
        <template
          slot="participants"
          slot-scope="props"
        >
          <avatar-image
            v-for="participant in props.rowData.participants"
            :key="participant.id"
            size="25"
            hide-name="true"
            :input-data="participant"
          />
        </template>
        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :href="openRequest(props.rowData, props.rowIndex)"
                :title="$t('Open Request')"
              >
                <i class="fas fa-caret-square-right fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Request')"
        :plural="$t('Requests')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
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
      this.$nextTick(() => {
        this.$refs.vuetable.normalizeFields();
      });
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
        },
        {
          label: "Name",
          field: "name",
          sortable: true,
          default: true,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
        },
        {
          label: "Participants",
          field: "participants",
          sortable: false,
          default: true,
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
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
        '<i class="fas fa-circle text-' +
        color +
        '"></i> <span>' +
        this.$t(label) +
        "</span>"
      );
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
  },
};
</script>
<style>
</style>
