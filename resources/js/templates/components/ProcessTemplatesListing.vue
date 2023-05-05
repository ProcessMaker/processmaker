<template>
    <div class="data-table">
      <data-loading
              :for="/templates\/process/"
              v-show="shouldShowLoader"
              :empty="$t('No Data Available')"
              :empty-desc="$t('')"
              empty-icon="noData"
      />
      <div v-show="!shouldShowLoader" class="card card-body process-template-table-card" data-cy="processes-template-table">
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
            <span v-uni-id="props.rowData.id.toString()">{{props.rowData.name}}
              <small class="text-muted d-block">{{ props.rowData.description | str_limit(70) }}</small>
            </span>
          </template>

          <template slot="owner" slot-scope="props">
            <avatar-image
                    class="d-inline-flex pull-left align-items-center"
                    size="25"
                    :input-data="props.rowData.user"
                    :hide-name="false"
            ></avatar-image>
          </template>

          <template slot="actions" slot-scope="props">
            <ellipsis-menu 
              @navigate="onNavigate"
              :actions="actions"
              :permission="permission"
              :data="props.rowData"
              :is-documenter-installed="isDocumenterInstalled"
              :divider="true"
            />
          </template>
        </vuetable>
  
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
  
    const uniqIdsMixin = createUniqIdsMixin();
  
    export default {
      components: {EllipsisMenu},
      mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
      props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "processName"],
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
  
          fields: [
            {
              title: () => this.$t("Name"),
              name: "__slot:name",
              field: "name",
              sortField: "name"
            },
            {
              title: () => this.$t("Category"),
              name: "categories",
              sortField: "category.name",
              callback(categories) {
                return categories.map(item => item.name).join(', ');
              }
            },
            {
              title: () => this.$t("Template Author"),
              name: "__slot:owner",
              callback: this.formatUserName
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
        formatStatus(status) {
          status = status.toLowerCase();
          let bubbleColor = {
            active: "text-success",
            inactive: "text-danger",
            draft: "text-warning",
            archived: "text-info"
          };
          let response =
              '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
          status = status.charAt(0).toUpperCase() + status.slice(1);
          return '<div style="white-space:nowrap">' + response + status + "</div>";
        },
        formatUserName(user) {
          return (
              (user.avatar
                  ? this.createImg({
                    src: user.avatar,
                    class: "rounded-user"
                  })
                  : '<i class="fa fa-user rounded-user"></i>') +
              "<span>" +
              user.fullname +
              "</span>"
          );
        },
        createImg(properties) {
          let container = document.createElement("div");
          let node = document.createElement("img");
          for (let property in properties) {
            node.setAttribute(property, properties[property]);
          }
          container.appendChild(node);
          return container.innerHTML;
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
             //   console.log('FETCH PERMISSIONS', this.permission);
              });
        },
        addWarningMessages(data) {
          data.data = data.data.map(template => {
            template.warningMessages = [];
            // if (!template.manager_id) {
            //   process.warningMessages.push(this.$t('Process Manager not configured.'));
            // }
            // if (template.warnings) {
            //   process.warningMessages.push(this.$t('BPMN validation issues. Request cannot be started.'));
            // }
            return template;
          });
          return data;
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
  