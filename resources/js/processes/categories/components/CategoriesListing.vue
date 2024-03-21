<template>
    <div class="data-table">
        <data-loading
            :for="/categories\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
        />
        <div v-show="!shouldShowLoader" class="categories-table-card" data-cy="categories-table">
          <filter-table
            :headers="fields"
            :data="data"
            table-name="categories"
            style="height: calc(100vh - 355px);"
          >
         <!-- Slot Table Header filter Button -->
            <template v-for="(column, index) in fields" v-slot:[`filter-${column.field}`]>
              <div
                v-if="column.sortable"
                :key="index"
                @click="handleEllipsisClick(column)"
              >
                <i
                  :class="['fas', {
                    'fa-sort': column.direction === 'none',
                    'fa-sort-up': column.direction === 'asc',
                    'fa-sort-down': column.direction === 'desc',
                  }]"
                ></i>
              </div>
            </template>
            <template v-for="(row, rowIndex) in data.data" v-slot:[`row-${rowIndex}`]>
              <td
                v-for="(header, colIndex) in fields"
                :key="colIndex"
                :data-cy="`category-table-td-${rowIndex}-${colIndex}`"
              >
                <div
                  v-if="containsHTML(row[header.field])"
                  v-html="sanitize(row[header.field])"
                  :data-cy="`category-table-html-${rowIndex}-${colIndex}`"
                >
                </div>
                <template v-else>
                  <template 
                    v-if="isComponent(row[header.field])"
                    :data-cy="`category-table-component-${rowIndex}-${colIndex}`"
                  >
                    <component
                      :is="row[header.field].component"
                      v-bind="row[header.field].props"
                    >
                    </component>
                  </template>
                  <template
                    v-else
                    :data-cy="`category-table-field-${rowIndex}-${colIndex}`"
                  >
                    <template v-if="header.field === 'status'">
                      <i
                        :class="`fas fa-circle ${ row['bubble_color'] } small`"
                      >
                      </i>
                      <span class="text-capitalize">
                        {{ $t(row["status"].toLowerCase().charAt(0).toUpperCase() + row["status"].toLowerCase().slice(1)) }}
                      </span>
                    </template>
                    <ellipsis-menu
                      v-if="header.field === 'actions'"
                      @navigate="onNavigate"
                      :actions="actions"
                      :permission="permissions"
                      :data="row"
                      :divider="true"
                      data-cy="category-ellipsis"
                    />
                    <template v-if="header.field !== 'status'">
                      <div
                        :style="{ maxWidth: header.width + 'px' }"
                      >
                        {{ getNestedPropertyValue(row, header) }}
                      </div>
                    </template>
                  </template>
                </template>
              </td>
            </template>
          </filter-table>
          <pagination-table
            :meta="data.meta"
            @page-change="changePage"
            @per-page-change="changePerPage"
            data-cy="category-pagination"
          />
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
  import paginationTable from "../../../components/shared/PaginationTable.vue";
  import FilterTableBodyMixin from "../../../components/shared/FilterTableBodyMixin";
  const uniqIdsMixin = createUniqIdsMixin();

  export default {
    components: {EllipsisMenu, paginationTable},
    mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, FilterTableBodyMixin],
    props: ["filter", "permissions", "apiRoute", "include", "labelCount", "count", "loadOnStart"],
    data () {
      return {
        fetchFlag: 0, 
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
            name: "__slot:name",
            sortField: "name",
            label: this.$t('Name'),
            field: "name",
            width: 200,
            sortable: true,
            truncate: true,
            direction: "none",
          },
          {
            name: "status",
            sortField: "status",
            label: this.$t('Status'),
            field: "status",
            width: 160,
            sortable: true,
            direction: "none",
            callback: this.formatStatus
          },
          {
            name: this.count,
            label: this.labelCount,
            field: this.count,
            width: 160,
            sortable: true,
            direction: "none",
            sortField: this.count
          },
          {
            name: "updated_at",
            sortField: "updated_at",
            label: this.$t('Modified'),
            field: "updated_at",
            width: 160,
            sortable: true,
            format: "datetime",
            direction: "none",
            callback: "formatDate"
          },
          {
            name: "created_at",
            sortField: "created_at",
            label: this.$t('Created'),
            field: "created_at",
            width: 160,
            sortable: true,
            format: "datetime",
            direction: "none",
            callback: "formatDate"
          },
          {
            name: "__slot:actions",
            field: "actions",
            title: "",
            width: 60,
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
      transform(data) {
        // Clean up fields for meta pagination so vue table pagination can understand
        data.meta.last_page = data.meta.total_pages;
        data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
        data.meta.to = data.meta.from + data.meta.count;
        data.data = this.jsonRows(data.data);

        for (let record of data.data) {
          //format Status
          record["bubble_color"] = this.formatBubbleColor(record["status"]);
        }
        return data;
      },
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
            if (response.data.data.length === 0 && this.fetchFlag === 0){
              this.page = 1;
              this.fetch();
              this.fetchFlag = 1;
            } else {
              this.fetchFlag = 0;
            }
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
      formatBubbleColor(status) {
        status = status.toLowerCase();
        let bubbleColor = {
          active: "text-success",
          inactive: "text-danger",
          draft: "text-warning",
          archived: "text-info"
        };
        return bubbleColor[status];
      },
      handleEllipsisClick(categoryColumn) {
        this.fields.forEach(column => {
          if (column.field !== categoryColumn.field) {
            column.direction = "none";
            column.filterApplied = false;
          }
        });

        if (categoryColumn.direction === "asc") {
          categoryColumn.direction = "desc";
        } else if (categoryColumn.direction === "desc") {
          categoryColumn.direction = "none";
          categoryColumn.filterApplied = false;
        } else {
          categoryColumn.direction = "asc";
          categoryColumn.filterApplied = true;
        }

        if (categoryColumn.direction !== "none") {
          const sortOrder = [
            {
              sortField: categoryColumn.sortField || categoryColumn.field,
              direction: categoryColumn.direction,
            },
          ];
          this.dataManager(sortOrder);
        } else {
          this.fetch();
        }
      },
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

.categories-table-card {
  padding: 0;
}
</style>
