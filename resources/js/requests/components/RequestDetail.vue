<template>
    <div class="data-table">
        <div>
            <vuetable :no-data-template="$t('No Data Available')"
                      :dataManager="dataManager"
                      :sortOrder="sortOrder"
                      :css="css"
                      :api-mode="false"
                      :fields="fields"
                      :data="data"
                      data-path="data"
                      pagination-path="meta"
            >
                <template slot="ids" slot-scope="props">
                    <b-link v-if="isEditable(props.rowData)"
                            :href="onAction('edit', props.rowData, props.rowIndex)">
                        #{{props.rowData.id}}
                    </b-link>
                    <span v-else>#{{props.rowData.id}}</span>
                </template>
                <template slot="name" slot-scope="props">
                    <b-link v-if="isEditable(props.rowData)"
                            :href="onAction('edit', props.rowData, props.rowIndex)">
                        {{props.rowData.element_name}}
                    </b-link>
                    <span v-else>{{props.rowData.element_name}}</span>
                </template>

                <template slot="participants" slot-scope="props">
                    <span v-if="props.rowData.is_self_service">{{$t('Self Service')}}</span>
                    <avatar-image v-else class="d-inline-flex pull-left align-items-center"
                                  size="25"
                                  :input-data="props.rowData.participants"
                    ></avatar-image>
                </template>
            </vuetable>
        </div>
    </div>
</template>

<script>
  import datatableMixin from "../../components/common/mixins/datatable";
  import moment from "moment";

  export default {
    mixins: [datatableMixin],
    props: ["processRequestId", "status"],
    data() {
      return {
        orderBy: "due_at",

        sortOrder: [
          {
            field: "due_at",
            sortField: "due_at",
            direction: "asc"
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
            title: () => this.$t("TASK"),
            name: "__slot:name",
            field: "element_name",
            sortField: "element_name"
          },
          {
            title: () => this.$t("ASSIGNED"),
            name: "__slot:participants",
            field: "participants",
            sortField: "user.lastname"
          },
          {
            title: () => this.$t("DUE"),
            name: "due_at",
            sortField: "due_at"
          }
        ]
      };
    },
    methods: {
      __(variable) {
        return __(variable);
      },
      canClaim(row) {
        let assignable = false;
        if (row.assignable_users instanceof Array) {
          assignable = !!row.assignable_users.find(user => {
            return String(user.id) === String(window.ProcessMaker.user.id);
          });
        }
        return !row.user_id && row.is_self_service && assignable;
      },
      isEditable(row) {
        return String(row.user_id) === String(window.ProcessMaker.user.id) || this.canClaim(row) || row.status !== "ACTIVE";
      },
      onAction(action, rowData, index) {
        switch (action) {
          case "edit":
            return "/tasks/" + rowData.id + "/edit";
            break;
        }
      },
      formatDueDate(value, status) {
        let color = "text-dark";
        if (status === "overdue") {
          color = "badge badge-danger";
        }

        return (
          '<span class="' + color + '">' + this.formatDate(value) + "</span>"
        );
      },
      transform(data) {
        // Clean up fields for meta pagination so vue table pagination can understand
        data.meta.last_page = data.meta.total_pages;
        data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
        data.meta.to = data.meta.from + data.meta.count;
        //load data for participants
        for (let record of data.data) {
          record["participants"] = record["user"] ? [record["user"]] : [];

          let color = "text-primary";
          if (record["status"] === "overdue") {
            color = "badge badge-danger";
          }

          record["due_at"] = this.formatDueDate(
            record["due_at"],
            record["status"]
          );
        }
        return data;
      },
      fetch() {
        // Load from our api client
        ProcessMaker.apiClient
          .get(
            "tasks?page=" +
            this.page +
            "&include=user,assignableUsers" +
            "&process_request_id=" +
            this.processRequestId +
            "&status=" +
            this.status +
            "&per_page=" +
            this.perPage +
            this.getSortParam()
          )
          .then(response => {
            this.data = this.transform(response.data);
            this.loading = false;
          });
      },
      getSortParam: function () {
        if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
          return (
            "&order_by=" +
            this.sortOrder[0].sortField +
            "&order_direction=" +
            this.sortOrder[0].direction
          );
        } else {
          return "";
        }
      }
    }
  };
</script>

<style lang="scss" scoped>
    /deep/ tr td:nth-child(3) {
        padding: 6px 10px;
    }
</style>
