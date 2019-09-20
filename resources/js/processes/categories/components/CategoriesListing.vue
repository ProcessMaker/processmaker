<template>
    <div class="data-table">
        <data-loading
            :for="/categories\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
        />
        <div v-show="!shouldShowLoader" class="card card-body table-card">
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
                <template slot="actions" slot-scope="props">
                    <div class="actions">
                        <div class="popout">
                            <b-btn
                                variant="link"
                                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                                v-b-tooltip.hover
                                :title="$t('Edit')"
                                v-if="permission.includes('edit-categories')"
                            >
                                <i class="fas fa-pen-square fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                variant="link"
                                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                                v-b-tooltip.hover
                                :title="$t('Remove')"
                                v-if="permission.includes('delete-categories') && props.rowData[count] == 0"
                            >
                                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
                            </b-btn>
                        </div>
                    </div>
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

  export default {
    mixins: [datatableMixin, dataLoadingMixin],
    props: ["filter", "permission", "apiRoute", "location", "include", "labelCount", "count", "loadOnStart"],
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
        fields: [
          {
            title: () => this.$t("Name"),
            name: "name",
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
      onAction (action, data, index) {
        switch (action) {
          case "edit-item":
            window.location = this.location + "/" + data.id + "/edit";
            break;
          case "remove-item":
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
                    ProcessMaker.alert("The category was deleted.", "success");
                    this.$emit("reload");
                  });

              }
            );
            break;
        }
      },
      formatStatus (value) {
        let response =
          "<i class=\"fas fa-circle " + value.toLowerCase() + "\"></i> ";
        return response + _.capitalize(value);
      }
    }
  };
</script>

<style lang="scss" scoped>
    /deep/ i.fa-circle {
    &.active {
         color: green;
     }
    &.inactive {
         color: red;
     }
    }
</style>
