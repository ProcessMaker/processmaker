<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/deleted_users\?page/"
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
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        :no-data-template="$t('No Data Available')"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="username"
          slot-scope="props"
        >
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.username }}</span>
        </template>
        <template
          slot="avatar"
          slot-scope="props"
        >
          <avatar-image
            size="25"
            :input-data="props.rowData"
            hide-name="true"
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
                :title="$t('Restore User')"
                @click="restoreUser(props.rowData, props.rowIndex)"
              >
                <i class="fas fa-trash-restore fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Deleted User')"
        :plural="$t('Deleted Users')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import AvatarImage from "../../../components/AvatarImage.vue";

const uniqIdsMixin = createUniqIdsMixin();
Vue.component("AvatarImage", AvatarImage);

export default {
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      localLoadOnStart: false,
      orderBy: "username",
      data: [],
      // Our listing of users
      sortOrder: [
        {
          field: "username",
          sortField: "username",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id",
        },
        {
          title: () => this.$t("Username"),
          name: "__slot:username",
          sortField: "username",
        },
        {
          title: () => this.$t("Full Name"),
          name: "fullname",
          sortField: "fullname",
        },
        {
          title: () => this.$t("Avatar"),
          name: "__slot:avatar",
          field: "user",
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status",
          callback: this.formatStatus,
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate",
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate",
        },
        {
          title: () => this.$t("Last Login"),
          name: "loggedin_at",
          sortField: "loggedin_at",
          callback: "formatDate",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  watch: {
    filter() {
      this.page = 1;
    },
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-deleted-users", (val) => {
      this.localLoadOnStart = val;
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
  methods: {
    formatStatus(status) {
      status = status.toLowerCase();
      const bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        draft: "text-warning",
        archived: "text-info",
      };
      return (
        `<i class="fas fa-circle ${
          bubbleColor[status]
        } small"></i><span class="text-capitalize"> ${
          this.$t(status.charAt(0).toUpperCase() + status.slice(1))
        }</span>`
      );
    },
    restoreUser(data, index) {
      const $body = {
        id: data.id,
      };

      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to restore the user {{item}}?", { item: data.fullname }),
        "",
        () => {
          ProcessMaker.apiClient.put("users/restore", $body).then((response) => {
            ProcessMaker.alert(
              this.$t("The user was restored"),
              "success",
            );
            ProcessMaker.EventBus.$emit("api-data-deleted-users", true);
          });
        },
      );
    },
    fetch() {
      if (!this.localLoadOnStart) {
        this.data = [];
        return;
      }
      this.loading = true;
      // Change method sort by user
      this.orderBy = this.orderBy === "fullname" ? "firstname" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `deleted_users?page=${
            this.page
          }&per_page=${
            this.perPage
          }&filter=${
            this.filter
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
  },
};
</script>

<style lang="scss" scoped>
</style>
