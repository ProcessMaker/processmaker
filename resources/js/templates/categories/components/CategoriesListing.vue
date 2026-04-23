<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/categories\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body table-card"
      data-cy="categories-table"
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
          slot="name"
          slot-scope="props"
        >
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.name }}</span>
        </template>
        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-if="permissions.edit"
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :title="$t('Edit')"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
              >
                <i class="fas fa-pen-square fa-lg fa-fw" />
              </b-btn>
              <b-btn
                v-if="permissions.delete && props.rowData[count] == 0"
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :title="$t('Delete')"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Category')"
        :plural="$t('Categories')"
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

const uniqIdsMixin = createUniqIdsMixin();

export default {
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permissions", "apiRoute", "include", "labelCount", "count", "loadOnStart"],
  data() {
    return {
      localLoadOnStart: !!this.loadOnStart,
      orderBy: "name",
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          sortField: "name",
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status",
          callback: this.formatStatus,
        },
        {
          title: () => this.labelCount,
          name: this.count,
          sortField: this.count,
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
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-category", (val) => {
      this.localLoadOnStart = val;
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
  methods: {
    fetch() {
      if (!this.localLoadOnStart) {
        this.data = [];
        return;
      }
      this.loading = true;

      // Load from our api client
      ProcessMaker.apiClient
        .get(`${this.apiRoute
        }?page=${
          this.page
        }&per_page=${
          this.perPage
        }&filter=${
          this.filter
        }&order_by=${
          this.orderBy
        }&order_direction=${
          this.orderDirection
        }&include=${this.include}`)
        .then((response) => {
          if (response.data.data.length === 0 && !this.filter) {
            $("#createCategory")
              .modal("show");
          } else {
            this.data = this.transform(response.data);
            this.loading = false;
            this.apiNoResults = false;
          }
        });
    },
    onAction(action, data, index) {
      switch (action) {
        case "edit-item":
          this.$emit("edit", data);
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            `<b>${
              this.$t("Are you sure you want to delete {{item}}?", {
                item: data.name,
              })
            }</b>`,
            "",
            () => {
              ProcessMaker.apiClient.delete(`${this.apiRoute}/${data.id}`)
                .then(() => {
                  ProcessMaker.alert(this.$t("The category was deleted."), "success");
                  this.$emit("reload");
                });
            },
          );
          break;
      }
    },
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
  },
};
</script>

<style lang="scss" scoped>
    :deep(i.fa-circle) {
    &.active {
         color: green;
     }
    &.inactive {
         color: red;
     }
    }
</style>
