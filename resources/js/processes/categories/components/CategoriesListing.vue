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
              style="height: 450px;"
            >
            <template v-for="(row, rowIndex) in data.data" v-slot:[`row-${rowIndex}`]>
              <td
                v-for="(header, colIndex) in fields"
                :key="colIndex"
              >
                <div v-if="containsHTML(row[header.field])" v-html="sanitize(row[header.field])"></div>
                <template v-else>
                  <template v-if="isComponent(row[header.field])">
                    <component
                      :is="row[header.field].component"
                      v-bind="row[header.field].props"
                    >
                    </component>
                  </template>
                  <template v-else>
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
                        :id="`element-${rowIndex}-${colIndex}`"
                        :class="{ 'pm-table-truncate': header.truncate }"
                        :style="{ maxWidth: header.width + 'px' }"
                      >
                        {{ row[header.field] }}
                        <b-tooltip
                          v-if="header.truncate"
                          :target="`element-${rowIndex}-${colIndex}`"
                          custom-class="pm-table-tooltip"
                        >
                          {{ row[header.field] }}
                        </b-tooltip>
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
  const uniqIdsMixin = createUniqIdsMixin();

  export default {
    components: {EllipsisMenu, paginationTable},
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
            sortField: "name",
            label: "NAME",
            field: "name",
            width: 200,
            sortable: true,
          },
          {
            title: () => this.$t("Status"),
            name: "status",
            sortField: "status",
            label: "STATUS",
            field: "status",
            width: 160,
            sortable: true,
            callback: this.formatStatus
          },
          {
            title: () => this.labelCount,
            name: this.count,
            label: this.labelCount,
            field: this.count,
            width: 160,
            sortable: true,
            sortField: this.count
          },
          {
            title: () => this.$t("Modified"),
            name: "updated_at",
            sortField: "updated_at",
            label: "MODIFIED",
            field: "updated_at",
            width: 160,
            sortable: true,
            format: "datetime",
            callback: "formatDate"
          },
          {
            title: () => this.$t("Created"),
            name: "created_at",
            sortField: "created_at",
            label: "CREATED",
            field: "created_at",
            width: 160,
            sortable: true,
            format: "datetime",
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
      containsHTML(text) {
          const doc = new DOMParser().parseFromString(text, 'text/html');
          return Array.from(doc.body.childNodes).some(node => node.nodeType === Node.ELEMENT_NODE);
        },
        isComponent(content) {
          if (content && typeof content === 'object') {
            return content.component && typeof content.props === 'object';
          }
          return false;
        },
        sanitize(html) {
          let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
          cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
          cleanHtml = cleanHtml.replace(
            /<(?!b|\/b|br|img|a|input|hr|i|link|meta|time|button|select|textarea|datalist|progress|meter|span)[^>]*>/gi,
            "",
          );
          cleanHtml = cleanHtml.replace(/\s+/g, " ");

          return cleanHtml;
        },
        changePage(page) {
          this.page = page;
          this.fetch();
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
