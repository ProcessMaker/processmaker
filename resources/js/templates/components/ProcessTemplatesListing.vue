<template>
    <div class="data-table">
      <data-loading
              :for="/\/processes\?page/"
              v-show="shouldShowLoader"
              :empty="$t('No Data Available')"
              :empty-desc="$t('')"
              empty-icon="noData"
      />
      <div v-show="!shouldShowLoader" class="process-template-table-card" data-cy="processes-template-table">
        <filter-table
          :headers="fields"
          :data="data"
          table-name="templates"
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
            :data-cy="`template-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              v-html="sanitize(row[header.field])"
              :data-cy="`template-table-html-${rowIndex}-${colIndex}`"
            >
            </div>
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`template-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                >
                </component>
              </template>
              <template
                v-else
                :data-cy="`template-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'name'">
                  <div
                    :id="`element-${row.id}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    <span v-uni-id="row.id.toString()">{{row.name}}
                      <small class="text-muted d-block">{{ row.description | str_limit(70) }}</small>
                    </span>
                    <b-tooltip
                      v-if="header.truncate"
                      :target="`element-${row.id}`"
                      custom-class="pm-table-tooltip"
                      @show="checkIfTooltipIsNeeded"
                    >
                      {{ row[header.field] }}
                    </b-tooltip>
                  </div>
                </template>
                <ellipsis-menu
                  v-if="header.field === 'actions'"
                  @navigate="onNavigate"
                  :actions="actions"
                  :permission="permission"
                  :data="row"
                  :is-documenter-installed="isDocumenterInstalled"
                  :divider="true"
                />
                <template v-if="header.field !== 'name'">
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
          data-cy="template-pagination"
        />
        <pagination
          :single="$t('Template')"
          :plural="$t('Templates')"
          :perPageSelectEnabled="true"
          @changePerPage="changePerPage"
          @vuetable-pagination:change-page="onPageChange"
          ref="pagination"
        ></pagination>
      </div>
    </div>
</template>

<script>
  Vue.filter('str_limit', function (value, size) {
    if (!value) return '';
    value = value.toString();

    if (value.length <= size) {
      return value;
    }
    return value.substr(0, size) + '...';
  });
    import datatableMixin from "../../components/common/mixins/datatable";
    import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
    import { createUniqIdsMixin } from "vue-uniq-ids";
    import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
    import paginationTable from "../../components/shared/PaginationTable.vue";
    import FilterTableBodyMixin from "../../components/shared/FilterTableBodyMixin";
    import templateMixin from "../../processes/screen-templates/mixins/templateMixin.js";
  
    const uniqIdsMixin = createUniqIdsMixin();
  
    export default {
      components: { EllipsisMenu, paginationTable },
      mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, FilterTableBodyMixin, templateMixin],
      props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "processName"],
      data() {
        return {
          orderBy: "name",
          previousFilter: "",
          sortOrder: [
            {
              field: "name",
              sortField: "name",
              direction: "asc"
            }
          ],
  
          fields: [
            {
              label: "NAME",
              field: "name",
              width: 200,
              sortable: true,
              truncate: true,
              direction: "none",
            },
            {
              label: "CATEGORY",
              field: "category_list",
              width: 160,
              sortable: true,
              direction: "none",
              sortField: "category.name",
            },
            {
              label: "TEMPLATE AUTHOR",
              field: "owner",
              width: 160,
              sortable: true,
              direction: "none",
              sortField: "user.username",
            },
            {
              label: "VERSION",
              field: "version",
              width: 100,
              sortable: true,
              direction: "none",
            },
            {
              label: "VERSION DATE",
              field: "updated_at",
              format: "datetime",
              width: 200,
              sortable: true,
              direction: "none",
            },
            {
              label: "CREATED",
              field: "created_at",
              format: "datetime",
              width: 200,
              sortable: true,
              direction: "none",
            },
            {
              name: "__slot:actions",
              field: "actions",
              title: "",
              width: 60,
            }
          ],
          actions: [
            { value: "view-documentation", content: "Template Documentation", link: true, href:"/modeler/template/{{id}}/print", permission: "view-process-templates", icon: "fas fa-sign", conditional: "isDocumenterInstalled"},
            { value: "edit-designer", content: "Edit Template", link: true, href:"/modeler/templates/{{id}}", permission: "edit-process-templates", icon: "fas fa-edit"},
            { value: "export-item", content: "Export Template", permission: "export-process-templates", icon: "fas fa-file-export"},
            { value: "edit-item", content: "Configure Template", link: true, href:"/template/process/{{id}}/configure", permission: "edit-process-templates", icon: "fas fa-cog"},
            { value: "delete-item", content: "Delete Template", permission: "delete-process-templates", icon: "fas fa-trash"},
          ],
        };
      },
      created () {
        ProcessMaker.EventBus.$on("api-data-process-templates", (val) => {
          this.fetch();
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
            record["owner"] = this.formatAvatar(record["user"]);
            record["category_list"] = this.formatCategory(record["categories"]);
          }
          return data;
        },
        exportTemplate(template) {
          ProcessMaker.apiClient({
            method: 'POST',
            url: `export/process_templates/download/` + template.id,
            responseType: 'blob',
            data: {
              template,
            }
          }).then(response => {
            const exportInfo = JSON.parse(response.headers['export-info']);
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href = url;

            link.setAttribute("download", `${exportInfo.name.replace(' ', '_')}.json`);
            document.body.appendChild(link);
            link.click();
            ProcessMaker.alert(`The template ${exportInfo.name} was exported`, 'success');
          });
        },
        onNavigate(action, data, index) {
          let putData = {
            name: data.name,
            description: data.description,
          };
          switch (action.value) {
            case "edit-designer":
              this.goToDesigner(data.id);
              break;
            case "export-item":
              this.exportTemplate(data);
              break;
            case "create-template":
              this.createTemplate(data.id);
              break;
            case "delete-item":
              ProcessMaker.confirmModal(
                  this.$t("Caution!"),
                  this.$t("Are you sure you want to delete the process template '") +
                  data.name +
                  "'?",
                  "",
                  () => {
                    ProcessMaker.apiClient
                        .delete("template/process/" + data.id)
                        .then(response => {
                          ProcessMaker.alert(
                              this.$t("The process template was deleted."),
                              "success"
                          );
                          this.$refs.pagination.loadPage(1);
                        });
                  }
              );
            break;
          }
        },
        fetch() {
          this.loading = true;
          this.apiDataLoading = true;
          //change method sort by user
          this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
          //change method sort by slot name
          this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;
  
          let url =
              this.status === null || this.status === "" || this.status === undefined
                  ? "templates/process?"
                  : "templates?status=" + this.status + "&";
          let filter = this.filter;
          if (this.previousFilter !== filter) {
            this.page = 1;
          }
          this.previousFilter = filter;
          // Load from our api client
          ProcessMaker.apiClient
              .get(
                  url +
                  "page=" +
                  this.page +
                  "&per_page=" +
                  this.perPage +
                  "&filter=" +
                  this.filter +
                  "&order_by=" +
                  this.orderBy +
                  "&order_direction=" +
                  this.orderDirection +
                  "&include=user,category,categories"
              )
              .then(response => {
                const data = this.addWarningMessages(response.data);
                this.data = this.transform(data);
                this.apiDataLoading = false;
                this.apiNoResults = false;
                this.loading = false;
              });
        },
        addWarningMessages(data) {
          data.data = data.data.map(template => {
            template.warningMessages = [];
            return template;
          });
          return data;
        },
        handleEllipsisClick(templateColumn) {
          this.fields.forEach(column => {
            if (column.field !== templateColumn.field) {
              column.direction = "none";
              column.filterApplied = false;
            }
          });

          if (templateColumn.direction === "asc") {
            templateColumn.direction = "desc";
          } else if (templateColumn.direction === "desc") {
            templateColumn.direction = "none";
            templateColumn.filterApplied = false;
          } else {
            templateColumn.direction = "asc";
            templateColumn.filterApplied = true;
          }

          if (templateColumn.direction !== "none") {
            const sortOrder = [
              {
                sortField: templateColumn.sortField || templateColumn.field,
                direction: templateColumn.direction,
              },
            ];
            this.dataManager(sortOrder);
          } else {
            this.fetch();
          }
        },
      },
    };
  </script>
  
  <style lang="scss" scoped>
    :deep(th#_updated_at) {
      width: 14%;
    }
  
    :deep(th#_created_at) {
      width: 14%;
    }
    .process-template-table-card {
      padding: 0;
    }
  </style>
  