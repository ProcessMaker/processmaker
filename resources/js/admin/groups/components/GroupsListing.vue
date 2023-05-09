<template>
  <div class="data-table">
    <data-loading
            :for="/groups\?page/"
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
        pagination-path="meta"
        :noDataTemplate="$t('No Data Available')"
      >
        <template slot="name" slot-scope="props">
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.name }}</span>
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
      <pagination
        :single="$t('Group')"
        :plural="$t('Groups')"
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
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {EllipsisMenu},
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      orderBy: "name",

      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc"
        }
      ],
      actions: [
        { value: "edit-item", content: "Edit Group", link: true, href: '/admin/groups/{{id}}/edit', icon: "fas fa-pen-square", permission:'edit-groups', ariaDescribedBy: 'data.id'},
        { value: "delete-item", content: "Delete Group", icon: "fas fa-trash-alt", permission: 'delete-groups',  ariaDescribedBy: 'data.id'},
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id"
        },
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          sortField: "Name"
        },
        {
          title: () => this.$t("Description"),
          name: "description",
          sortField: "description"
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
        {
          title: () => this.$t("# Members"),
          name: "group_members_count",
          sortField: "group_members_count"
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
        ' small"></i><span class="text-capitalize"> ' +
        this.$t(status.charAt(0).toUpperCase() + status.slice(1)) +
        '</span>'
      );
    },
    onDelete(data) {
      let that = this;
      ProcessMaker.confirmModal(
        this.$t('Caution!'),
        "<b>" + this.$t('Are you sure you want to delete {{item}}?', {item: data.name}) + "</b>",
        "",
        function() {
          ProcessMaker.apiClient.delete("groups/" + data.id).then(response => {
            ProcessMaker.alert(this.$t("The group was deleted."), "success");
            that.fetch();
          });
        }
      );
    },
    onNavigate(action, data) {
      switch (action.value) {
        case "delete-item":
          this.onDelete(data);
          break;
      }
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "groups?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=membersCount"
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
:deep(th#_total_users) {
  width: 150px;
  text-align: center;
}

:deep(.vuetable-th-status) {
  min-width: 90px;
}

:deep(.vuetable-th-members_count) {
  min-width: 90px;
}

:deep(th#_updated_at) {
  width: 14%;
}
:deep(th#_created_at) {
  width: 14%;
}
</style>
