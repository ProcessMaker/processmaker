<template>
  <div class="data-table">
    <div class="card-deck d-block d-sm-none">
      <div
        v-for="notification in data.data"
        :key="notification.id"
        class="card"
      >
        <div class="card-body shadow">
          <div class="d-flex align-items-center mb-3">
            <notification-user :notification="notification" />
          </div>
          <h5 class="card-title">
            <notification-message
              :notification="notification"
              :show-time="showTime"
            />
          </h5>
          <h6 class="card-subtitle mb-2 text-muted">
            {{ notification.read_at || 'N/A' }}
          </h6>
          <a
            v-if="notification.data.url"
            href="#"
            @click="redirectToURL(notification.data.url)"
          >More</a>
        </div>
      </div>
    </div>
    <data-loading
      v-show="loading"
      :for="/clients/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-if="!loading"
      class="card card-body table-card d-none d-sm-block"
    >
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :no-data-template="$t('No Data Available')"
        @vuetable:pagination-data="onPaginationData"
      >
        <!-- Change Status Slot -->
        <template
          slot="changeStatus"
          slot-scope="props"
        >
          <span
            v-if="props.rowData.read_at === null"
            style="cursor:pointer"
            class="far fa-envelope fa-lg blue-envelope"
            @click="read(props.rowData.id)"
          />

          <span
            v-if="props.rowData.read_at !== null"
            style="cursor:pointer"
            @click="unread(props.rowData)"
          >
            <i class="far fa-envelope-open fa-lg gray-envelope" />
          </span>
        </template>

        <!-- From Slot -->
        <template
          slot="from"
          slot-scope="props"
        >
          <notification-user :notification="props.rowData" />
        </template>

        <!-- Subject Slot -->
        <template
          slot="subject"
          slot-scope="props"
        >
          <a
            style="cursor: pointer;"
            @click="redirectToURL(props.rowData.data?.url)"
          >
            <span v-if="props.rowData.type === 'FILE_READY'" />
            <span v-else>
              <notification-message
                :notification="props.rowData"
                :style="{ fontSize: '14px' }"
              />
            </span>
          </a>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Task')"
        :plural="$t('Tasks')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import moment from "moment";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import AvatarImage from "../../components/AvatarImage";
import NotificationMessage from "./notification-message";
import NotificationUser from "./notification-user";

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    NotificationMessage,
    NotificationUser,
    AvatarImage,
  },
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["filter", "filterComments", "type", "showTime"],
  data() {
    return {
      response: null,
      orderBy: "",
      sortOrder: [],
      loading: false,
      fields: [
        {
          title: () => this.$t("Status"),
          name: "__slot:changeStatus",
          sortField: "read_at",
          width: "80px",
        },
        {
          title: () => this.$t("From"),
          name: "__slot:from",
          sortField: "from",
        },
        {
          title: () => this.$t("Message"),
          name: "__slot:subject",
          sortField: "subject",
        },
        {
          title: () => this.$t("Time"),
          name: "created_at",
          sortField: "created_at",
        },
      ],
    };
  },
  computed: {
    url() {
      return this.notification.data?.url;
    },
    isComment() {
      return this.data.type === "COMMENT";
    },
    timeFormat() {
      const parts = window.ProcessMaker.user.datetime_format.split(" ");
      parts.shift();
      return parts.join(" ");
    },
  },
  watch: {
    filterComments() {
      // this.transformResponse();
      this.fetch();
    },
    response() {
      this.transformResponse();
    },
  },
  mounted() {
    const params = new URL(document.location).searchParams;
    const successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    redirectToURL(url) {
      if (url && url !== "/") {
        window.location.href = url;
      }
    },
    toggleReadStatus(id, isRead) {
      const action = isRead ? ProcessMaker.unreadNotifications : ProcessMaker.removeNotifications;
      action([id]).then(() => {
        this.fetch();
      });
    },
    read(id) {
      ProcessMaker.removeNotifications([id]).then(() => {
        this.fetch();
        ProcessMaker.removeNotifications([id]);
      });
    },

    unread(notification) {
      ProcessMaker.unreadNotifications([notification.id]).then(() => {
        this.fetch();
        ProcessMaker.pushNotification(notification);
      });
    },

    getSortParam() {
      if (this.sortOrder.length > 0) {
        const { sortField, direction } = this.sortOrder[0];
        return `&order_by=${sortField}&order_direction=${direction}`;
      }
      return "";
    },

    transform(data) {
      return {
        data: data.data.map((record) => ({
          ...record,
          created_at: this.formatDate(record.created_at),
          read_at: record.read_at ? this.formatDate(record.read_at) : null,
        })),
        meta: data.meta,
      };
    },

    transformResponse() {
      // if (this.filterComments === true) {
      //   const filteredData = this.response.data.data.filter((item) => item.data && item.data.type === "COMMENT");
      //   this.data = this.transform({ data: filteredData });
      // } else if (this.filterComments === false) {
      //   const filteredData = this.response.data.data.filter((item) => item.data && item.data.type !== "COMMENT");
      //   this.data = this.transform({ data: filteredData });
      // } else {
      this.data = this.transform(this.response.data);
      // }
    },

    formatDate(dateTime) {
      const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December",
      ];

      const dateObj = new Date(dateTime);
      const currentDate = new Date();

      if (
        dateObj.getDate() === currentDate.getDate()
        && dateObj.getMonth() === currentDate.getMonth()
        && dateObj.getFullYear() === currentDate.getFullYear()
      ) {
        return moment(dateObj).format(this.timeFormat);
      }
      const month = dateObj.getMonth();
      const day = dateObj.getDate();
      const formattedMonth = months[month];
      return `${formattedMonth} ${day}`;
    },

    addLeadingZero(value) {
      return value < 10 ? `0${value}` : value;
    },

    fetch() {
      this.loading = true;
      if (this.cancelToken) {
        this.cancelToken();
        this.cancelToken = null;
      }
      const { CancelToken } = ProcessMaker.apiClient;

      const params = {
        page: this.page,
        per_page: this.perPage,
        filter: this.filter,
        comments: this.filterComments,
      };

      // Load from your API client (adjust the API endpoint and parameters as needed)
      ProcessMaker.apiClient
        .get("notifications", {
          params: {
            ...params,
            ...this.getSortParam(),
            include: "user",
          },
          cancelToken: new CancelToken((c) => {
            this.cancelToken = c;
          }),
        })
        .then((response) => {
          this.response = response;
          this.loading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.icon {
  width: 1em;
}
.gray-envelope {
  color: gray;
}
.blue-envelope {
  color: rgb(55, 55, 87);
}
:deep(.vuetable-th-slot-subject) {
  min-width: 450px;
  white-space: nowrap;
}
:deep(tr td:nth-child(1) span) {
  padding: 6px 0px 0px 12px;
}
</style>
