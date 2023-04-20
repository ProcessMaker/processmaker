<template>
    <div class="data-table">
        <data-loading
            :for="/categories\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
        />
        <div v-show="!shouldShowLoader" class="card card-body table-card" data-cy="categories-table">
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
                  <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.name }}</span>
                </template>
                <template slot="actions" slot-scope="props">
                  <ellipsis-menu 
                    @navigate="onNavigate"
                    :actions="actions"
                    :permission="permissions"
                    :data="props.rowData"
                    :divider="true"
                  />
                </template>
            </vuetable>
            <pagination
                :single="$t('Category')"
                :plural="$t('Categories')"
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
    components: {EllipsisMenu},
    mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
    props: ["filter", "permissions", "apiRoute", "include", "labelCount", "count", "loadOnStart"],
    data () {
      return {
        localLoadOnStart: !!this.loadOnStart,
        orderBy: "name",
        sortOrder: [
          {
            field: "name",
            sortField: "name",
            direction: "asc"
          }
        ],
        actions: [
          { value: "edit-item", content: "Edit Category", icon: "fas fa-edit", permission:'edit'},
          { value: "delete-item", content: "Delete Category", icon: "fas fa-trash", permission: 'delete'},
        ],
        fields: [
          {
            title: () => this.$t("Name"),
            name: "__slot:name",
            sortField: "name"
          },
          {
            title: () => this.$t("Status"),
            name: "status",
            sortField: "status",
            callback: this.formatStatus
          },
          {
            title: () => this.labelCount,
            name: this.count,
            sortField: this.count
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
    created () {
      ProcessMaker.EventBus.$on("api-data-category", (val) => {
        this.localLoadOnStart = val;
        this.fetch();
        this.apiDataLoading = false;
        this.apiNoResults = false;
      });
    },
    methods: {
      fetch () {
        if (!this.localLoadOnStart) {
          this.data = [];
          return;
        }
        this.loading = true;

        // Load from our api client
        ProcessMaker.apiClient
          .get(this.apiRoute +
            "?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=" + this.include
          )
          .then(response => {
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
      onNavigate (action, data, index) {
        switch (action.value) {
          case "edit-item":
            this.$emit('edit', data);
            break;
          case "delete-item":
            ProcessMaker.confirmModal(
              this.$t("Caution!"),
              "<b>" +
              this.$t("Are you sure you want to delete {{item}}?", {
                item: data.name
              }) +
              "</b>",
              "",
              () => {
                ProcessMaker.apiClient.delete(`${this.apiRoute}/${data.id}`)
                  .then(() => {
                    ProcessMaker.alert(this.$t("The category was deleted."), "success");
                    this.$emit("reload");
                  });

              }
            );
            break;
        }
      },
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
      }
    }
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
