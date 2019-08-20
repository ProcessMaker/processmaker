<template>
  <div class="data-table">
    <data-loading :for="/requests\?page/" v-show="shouldShowLoader" />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
      >
        <template slot="ids" slot-scope="props">
          <b-link @click="openRequest(props.rowData, props.rowIndex)">#{{props.rowData.id}}</b-link>
        </template>
        <template slot="participants" slot-scope="props">
          <avatar-image
            v-for="participant in props.rowData.participants"
            :key="participant.id"
            class="d-inline-flex pull-left align-items-center"
            size="25"
            hide-name="true"
            :input-data="participant"
          ></avatar-image>
        </template>
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-designer', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Open Request')"
              >
                <i class="fas fa-caret-square-right fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Request')"
        :plural="$t('Requests')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading.js";
import AvatarImage from "../../components/AvatarImage";
import moment from "moment";

Vue.component("avatar-image", AvatarImage);

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  data() {
    return {
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc"
        }
      ],
      fields: [
        {
          name: "__slot:ids",
          title: "#",
          field: "id",
          sortField: "id"
        },
        {
          title: () => this.$t("Name"),
          name: "name",
          sortField: "name"
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status"
        },
        {
          title: () => this.$t("Participants"),
          name: "__slot:participants"
        },
        {
          title: () => this.$t("Started"),
          name: "initiated_at",
          sortField: "initiated_at"
        },
        {
          title: () => this.$t("Completed"),
          name: "completed_at",
          sortField: "completed_at"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  beforeCreate() {
    let status = null;

    switch (Processmaker.status) {
      // if there is no status, meaning its on my requests, We should only show the in progress status
      case "":
        status = "In Progress";
        this.$parent.requester.push(Processmaker.user);
        break;
      case "in_progress":
        status = "In Progress";
        break;
      case "completed":
        status = "Completed";
        break;
    }
    if (status) {
      this.$parent.status.push({
        name: this.$t(status),
        value: status
      });
    }

    this.$parent.buildPmql();
  },
  methods: {
    onAction(action, data, index) {
      switch (action) {
        case "edit-designer":
          this.openRequest(data, index);
          break;
      }
    },
    openRequest(data, index) {
      window.location.href = "/requests/" + data.id;
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
      for (let record of data.data) {
        //Format dates
        record["initiated_at"] = this.formatDate(record["initiated_at"]);
        if (record["completed_at"]) {
          record["completed_at"] = this.formatDate(record["completed_at"]);
        } else {
          record["completed_at"] = "";
        }
        //format Status
        record["status"] = this.formatStatus(record["status"]);
      }
      return data;
    },
    fetch(resetPagination) {
      if (resetPagination) {
        this.page = 1;
      }

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "requests?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&include=process,participants" +
            "&pmql=" +
            encodeURIComponent(this.$parent.pmql) +
            "&order_by=" +
            (this.orderBy === "__slot:ids" ? "id" : this.orderBy) +
            "&order_direction=" +
            this.orderDirection +
            this.additionalParams
        )
        .then(response => {
          this.data = this.transform(response.data);
        });
    }
  }
};
</script>
<style>
</style>