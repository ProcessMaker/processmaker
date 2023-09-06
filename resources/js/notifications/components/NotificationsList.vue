<template>
  <div class="data-table">
    <div
      class="card card-body table-card"
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
      >
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
            @click="unread(props.rowData.id)"
          >
            <i class="far fa-envelope-open fa-lg gray-envelope" />
          </span>
        </template>

        <template
          slot="from"
          slot-scope="props"
        >
          <notification-user
            :notification="props.rowData"
          />
        </template>

        <template
          slot="subject"
          slot-scope="props"
        >
          <a :href="props.rowData.url">
            <span v-if="props.rowData.type==='FILE_READY'" />
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
import datatableMixin from "../../components/common/mixins/datatable";
import AvatarImage from "../../components/AvatarImage";
import NotificationMessage from "./notification-message";
import NotificationUser from "./notification-user";

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    NotificationMessage,
    NotificationUser,
  },
  mixins: [datatableMixin],
  props: ["filter", "type"],
  notification: {
    type: Object,
    required: true,
  },
  data() {
    return {

      orderBy: "",

      sortOrder: [
      ],
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
  },
  mounted: function mounted() {
    const params = new URL(document.location).searchParams;
    const successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    read(id) {
      ProcessMaker.removeNotifications([id]).then(() => {
        this.fetch();
      });
    },

    unread(id) {
      ProcessMaker.unreadNotifications([id]).then(() => {
        this.fetch();
      });
    },

    getSortParam() {
      if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
        return `&order_by=${this.sortOrder[0].sortField
        }&order_direction=${this.sortOrder[0].direction}`;
      }
      return "";
    },

    transform(data) {
      // eslint-disable-next-line no-restricted-syntax
      for (const record of data.data) {
        record.created_at = this.formatDate(record.created_at);
        if (record.read_at) {
          record.read_at = this.formatDate(record.read_at);
        } else {
          record.read_at = null;
        }
      }
      return data;
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
        const hours = dateObj.getHours();
        const minutes = dateObj.getMinutes();
        return `${this.addLeadingZero(hours)}:${this.addLeadingZero(minutes)}`;
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

      // Load from your API client (adjust the API endpoint and parameters as needed)
      ProcessMaker.apiClient
        .get(
          `notifications?page=${
            this.page
          }&per_page=${
            this.perPage
          }&filter=${
            this.filter
          }&status=${
            new URLSearchParams(window.location.search).get("status")
          }${this.getSortParam()}`,
          {
            cancelToken: new CancelToken((c) => {
              this.cancelToken = c;
            }),
          },
        )
        .then((response) => {
          if (this.type) {
            const filteredData = response.data.data.filter((item) => item.data && (item.data.type === this.type || !item.data.type));
            this.data = this.transform({ data: filteredData });
          } else {
            this.data = this.transform(response.data);
          }
          this.loading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
    .icon {
        width:1em;
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
