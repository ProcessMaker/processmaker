<template>
  <div class="data-table">
    <data-loading
      :for="/users\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader"  class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        :noDataTemplate="$t('No Data Available')"
        pagination-path="meta"
      >
        <template slot="username" slot-scope="props">
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.username }}</span>
        </template>
        <template slot="avatar" slot-scope="props">
          <avatar-image size="25" :input-data="props.rowData" hide-name="true"></avatar-image>
        </template>
        <template slot="actions" slot-scope="props">
          <ellipsis-menu
            @navigate="onNavigate"
            :actions="actions"
            :permission="permission"
            :data="props.rowData"
            :divider="true"
          />
        </template>
      </vuetable>
      <add-to-bundle
        asset-type="users"
        :setting="true"
      />
      <pagination
        :single="$t('User')"
        :plural="$t('Users')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>


<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import AvatarImage from "../../../components/AvatarImage";
import AddToBundle from "../../../components/shared/AddToBundle.vue";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();
Vue.component("avatar-image", AvatarImage);

export default {
  components: {EllipsisMenu, AddToBundle},
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      localLoadOnStart: true,
      orderBy: "username",
      data: [],
      // Our listing of users
      sortOrder: [
        {
          field: "username",
          sortField: "username",
          direction: "asc"
        },
      ],
      actions: [
        { value: "edit-item", content: "Edit User", link: true, href: '/admin/users/{{id}}/edit', icon: "fas fa-pen-square", permission:'edit-users', ariaDescribedBy: 'data.id'},
        { value: "add-to-bundle", content: "Add to Bundle", icon: "fp-add-outlined", permission: "admin", emit_on_root: 'add-to-bundle'},
        { value: "delete-item", content: "Delete User", icon: "fas fa-trash-alt", permission: 'delete-users'},
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id"
        },
        {
          title: () => this.$t("Username"),
          name: "__slot:username",
          sortField: "username"
        },
        {
          title: () => this.$t("Full Name"),
          name: "fullname",
          sortField: "fullname"
        },
        {
          title: () => this.$t("Avatar"),
          name: "__slot:avatar",
          field: "user"
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        },
        {
          title: () => this.$t("Last Login"),
          name: "loggedin_at",
          sortField: "loggedin_at",
          callback: "formatDate"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  watch: {
    filter() {
      this.page = 1;
    }
  },
  created() {
      ProcessMaker.EventBus.$on("api-data-users", (val) => {
        this.localLoadOnStart = val;
        this.fetch();
        this.apiDataLoading = false;
        this.apiNoResults = false;
      });
  },
  methods: {
    formatStatus(status) {
      status = status.toLowerCase();
      let bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        draft: "text-warning",
        archived: "text-info",
        blocked: "text-danger"
      };
      return (
        '<i class="fas fa-circle ' +
        bubbleColor[status] +
        ' small"></i><span class="text-capitalize"> ' +
        this.$t(status.charAt(0).toUpperCase() + status.slice(1)) +
        '</span>'
      );
    },
    goToEdit(data) {
      window.location = "/admin/users/" + data + "/edit";
    },
    onNavigate(action, data, index) {
      switch (action.value) {
        case "edit-item":
          this.goToEdit(data.id);
          break;
        case "delete-item":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t("Are you sure you want to delete the user") +
              " " +
              data.fullname +
              this.$t("?"),
            "",
            () => {
              ProcessMaker.apiClient
                .delete("users/" + data.id)
                .then(response => {
                  ProcessMaker.alert(
                    this.$t("The user was deleted."),
                    "danger"
                  );
                  ProcessMaker.EventBus.$emit("api-data-users", true);
                });
            }
          );
          break;
      }
    },
    fetch() {
      if (!this.localLoadOnStart) {
        //this.data = [];
        return;
      }
      this.loading = true;
      //change method sort by user
      this.orderBy = this.orderBy === "fullname" ? "firstname" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "users?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
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
</style>
