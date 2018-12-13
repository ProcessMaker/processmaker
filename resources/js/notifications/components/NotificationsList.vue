<template>
  <div class="data-table">
    <div class="card card-body table-card">
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
        <template slot="subject" slot-scope="props">
          <a v-bind:href="props.rowData.url">{{props.rowData.name}}</a>
        </template>

        <template slot="changeStatus" slot-scope="props">
          <span
            v-if="props.rowData.read_at === null"
            style="cursor:pointer"
            @click="read(props.rowData.id)"
            class="far fa-envelope fa-lg"
          ></span>
          
          <span
            v-if="props.rowData.read_at !==  null"
            style="cursor:pointer"
            @click="unread(props.rowData.id)"
          >
            <i class="far fa-envelope-open fa-lg"></i>
          </span>
        </template>
      </vuetable>
      <pagination
        single="Task"
        plural="Tasks"
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
import AvatarImage from "../../components/AvatarImage";
import moment from "moment";

Vue.component("avatar-image", AvatarImage);

export default {
  mixins: [datatableMixin],
  props: ["filter", "status"],
  data() {
    return {
      orderBy: "",

      sortOrder: [],
      fields: [
        {
          title: "STATUS",
          name: "__slot:changeStatus",
          sortField: "read_at",
          width: "80px"
        },
        {
          title: "SUBJECT",
          name: "__slot:subject",
          sortField: "name"
        },
        {
          title: "DATE CREATED",
          name: "created_at",
          sortField: "created_at"
        }
      ]
    };
  },
  methods: {
    read(id) {
      ProcessMaker.removeNotifications([id]);
      this.fetch();
    },

    unread(id) {
      ProcessMaker.unreadNotifications([id]);
      this.fetch();
    },

    getSortParam: function() {
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
    },

    transform(data) {
      for (let record of data.data) {
        record["created_at"] = this.formatDate(record["created_at"]);
        if (record["read_at"]) {
          record["read_at"] = this.formatDate(record["read_at"]);
        } else {
          record["read_at"] = null;
        }
      }
      return data;
    },

    fetch() {
      this.loading = true;
      if (this.cancelToken) {
        this.cancelToken();
        this.cancelToken = null;
      }
      const CancelToken = ProcessMaker.apiClient.CancelToken;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "notifications?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&status=" +
            this.status +
            "&filter=" +
            this.filter +
            this.getSortParam(),
          {
            cancelToken: new CancelToken(c => {
              this.cancelToken = c;
            })
          }
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  },
  mounted: function mounted() {
    let params = new URL(document.location).searchParams;
    let successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert("The request was completed successfully.", "success");
    }
  },
  methods: {
    read(id) {
      ProcessMaker.removeNotifications([id]);
      this.fetch();
    },

    unread(id) {
      ProcessMaker.unreadNotifications([id]);
      this.fetch();
    },

    getSortParam: function() {
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
    },

    transform(data) {
      for (let record of data.data) {
        record["created_at"] = this.formatDate(record["created_at"]);
        if (record["read_at"]) {
          record["read_at"] = this.formatDate(record["read_at"]);
        } else {
          record["read_at"] = null;
        }
      }
      return data;
    },

    fetch() {
      this.loading = true;
      if (this.cancelToken) {
        this.cancelToken();
        this.cancelToken = null;
      }
      const CancelToken = ProcessMaker.apiClient.CancelToken;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "notifications?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            this.getSortParam(),
          {
            cancelToken: new CancelToken(c => {
              this.cancelToken = c;
            })
          }
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
/deep/ .vuetable-th-slot-subject {
  min-width: 450px;
  white-space: nowrap;
}
/deep/ tr td:nth-child(1) span {
  padding: 6px 0px 0px 12px;
}
</style>

