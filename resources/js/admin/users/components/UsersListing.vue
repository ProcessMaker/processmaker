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
        :noDataTemplate="$t('No Data Available')"
        pagination-path="meta"
      >
      <template slot="avatar" slot-scope="props">
        <avatar-image size="25" :input-data="props.rowData" hide-name="true"></avatar-image>
      </template>
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                v-if="permission.includes('edit-users')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Delete')"
                v-if="permission.includes('delete-users')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
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
import AvatarImage from "../../../components/AvatarImage";
Vue.component("avatar-image", AvatarImage);


export default {
  mixins: [datatableMixin],
  props: ["filter", "permission"],
  data() {
    return {
      orderBy: "username",
      // Our listing of users
      sortOrder: [
        {
          field: "username",
          sortField: "username",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: () => this.$t("Username"),
          name: "username",
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
  methods: {
    formatStatus(status) {
      status = status.toLowerCase();
      let bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        draft: "text-warning",
        archived: "text-info"
      };
      return (
        '<i class="fas fa-circle ' +
        bubbleColor[status] +
        ' small"></i> ' +
        status.charAt(0).toUpperCase() +
        status.slice(1)
      );
    },
    goToEdit(data) {
      window.location = "/admin/users/" + data + "/edit";
    },
    onAction(action, data, index) {
      switch (action) {
        case "edit-item":
          this.goToEdit(data.id);
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            $t("Caution!"),
            $t("Are you sure you want to delete the user ") +
              data.fullname +
              $t("?"),
            "",
            () => {
              ProcessMaker.apiClient
                .delete("users/" + data.id)
                .then(response => {
                  ProcessMaker.alert($t("The user was deleted."), "danger");
                  this.$emit("reload");
                });
            }
          );
          break;
      }
    },
    fetch() {
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
