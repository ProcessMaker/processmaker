<template>
  <div class="data-table">
    <data-loading
            :for="/environment_variables\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader"  class="card card-body table-card" data-cy="env-table">
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
        <template slot="name" slot-scope="props">
          <span v-uni-id="props.rowData.id.toString()">{{props.rowData.name}}</span>
        </template>
        <template slot="actions" slot-scope="props">
          <ellipsis-menu
              @navigate="onAction"
              :actions="actions"
              :permission="permission"
              :data="props.rowData"
              :divider="false"
            />
        </template>
      </vuetable>
      <pagination
        :single="$t('Variable')"
        :plural="$t('Variables')"
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
import { createUniqIdsMixin } from "vue-uniq-ids";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      orderBy: "name",
      // Our listing of variables
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          sortField: "name"
        },
        {
          title: () => this.$t("Description"),
          name: "description",
          sortField: "description"
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
      ],
      actions: [
        {
          value: "edit-item",
          content: "Edit Variable",
          link: true,
          href: "/designer/environment-variables/{{id}}/edit",
          permission: "edit-environment_variables",
          icon: "fas fa-pen-square",
        },
        {
          value: "remove-item",
          content: "Delete",
          permission: "delete-environment_variables",
          icon: "fas fa-trash-alt",
        },
      ],
    };
  },
  methods: {
    onAction(action, data, index) {
      switch (action.value) {
        case "remove-item":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t("Are you sure you want to delete the environment variable {{ name }}?", {name: data.name}),
            "",
            () => {
              this.$emit("delete", data);
            }
          );
          break;
      }
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "environment_variables?page=" +
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
