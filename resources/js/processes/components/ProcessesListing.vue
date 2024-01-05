<template>
  <div class="data-table">
    <data-loading
      :for="/\/processes\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="processes-table-card" data-cy="processes-table">
      <filter-table
        :headers="fields"
        :data="data"
        style="height: 450px;"
      >
        <!-- Slot Table Body -->
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
                <template v-if="header.field === 'name'">
                  <div
                    :id="`element-${rowIndex}-${colIndex}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    <i tabindex="0"
                      v-b-tooltip
                      :title="row.warningMessages.join(' ')"
                      class="text-warning fa fa-exclamation-triangle"
                      :class="{'invisible': row.warningMessages.length == 0}">
                    </i>
                    <i tabindex="0"
                      v-if="row.status == 'ACTIVE' || row.status == 'INACTIVE'"
                      v-b-tooltip
                      :title="row.status"
                      class="mr-2"
                      :class="{ 'fas fa-check-circle text-success': row.status == 'ACTIVE', 'far fa-circle': row.status == 'INACTIVE' }">
                    </i>
                    <span
                      v-uni-id="row.id.toString()"
                    >
                      {{ row[header.field] }}
                    </span>
                    <b-tooltip
                      v-if="header.truncate"
                      :target="`element-${rowIndex}-${colIndex}`"
                      custom-class="pm-table-tooltip"
                    >
                      {{ row[header.field] }}
                    </b-tooltip>
                  </div>
                </template>
                <ellipsis-menu
                  v-if="header.field === 'actions'"
                  class="process-table"
                  @navigate="onProcessNavigate"
                  :actions="processActions"
                  :permission="permission"
                  :data="row"
                  :is-documenter-installed="isDocumenterInstalled"
                  :divider="false"
                />
                <template v-if="header.field !== 'name'">
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
      <create-template-modal id="create-template-modal" ref="create-template-modal" assetType="process" :currentUserId="currentUserId" :assetName="processTemplateName" :assetId="processId" />
      <create-pm-block-modal id="create-pm-block-modal" ref="create-pm-block-modal" :currentUserId="currentUserId" :assetName="pmBlockName" :assetId="processId" />
      <add-to-project-modal id="add-to-project-modal" ref="add-to-project-modal"  assetType="process" :assetId="processId" :assetName="assetName"/>
      <pagination-table
        :meta="data.meta"
        @page-change="changePage"
      />
      <pagination
        :single="$t('Process')"
        :plural="$t('Processes')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>      
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import { createUniqIdsMixin } from "vue-uniq-ids";
import isPMQL from "../../modules/isPMQL";
import TemplateExistsModal from "../../components/templates/TemplateExistsModal.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import { FilterTable } from "../../components/shared";
import processNavigationMixin from "../../components/shared/processNavigation";
import paginationTable from "../../components/shared/PaginationTable.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    TemplateExistsModal,
    CreateTemplateModal,
    EllipsisMenu,
    CreatePmBlockModal,
    AddToProjectModal,
    paginationTable,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, processNavigationMixin],
  props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "pmql", "processName", "currentUserId"],
  data() {
    return {
      orderBy: "name",
      processId: null,
      processTemplateName: '',
      pmBlockName: '',
      assetName: '',
      processData: {},
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        }
      ],

      fields: [
        {
          title: () => this.$t("Name"),
          label: "NAME",
          field: "name",
          width: 200,
          sortable: true,
        },
        {
          title: () => this.$t("Category"),
          label: "CATEGORY",
          field: "category_list",
          width: 160,
          sortable: true,
        },
        {
          title: () => this.$t("Owner"),
          label: "OWNER",
          field: "owner",
          width: 160,
          sortable: true,
        },
        {
          title: () => this.$t("Modified"),
          label: "MODIFIED",
          field: "updated_at",
          format: "datetime",
          width: 160,
          sortable: true,
        },
        {
          title: () => this.$t("Created"),
          label: "CREATED",
          field: "created_at",
          format: "datetime",
          width: 160,
          sortable: true,
        },
        {
          name: "__slot:actions",
          field: "actions",
          width: 60,

        },
      ]
    };
  },
  created () {
    ProcessMaker.EventBus.$on("api-data-process", (val) => {
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
        record["owner"] = this.formatOwner(record["user"]);
        record["category_list"] = this.formatCategory(record["categories"]);
      }
      return data;
    },
    showCreateTemplateModal(name, id) {
      this.processId = id;
      this.processTemplateName = name;
      this.$refs["create-template-modal"].show();
    },
    showPmBlockModal(name, id) {
      this.processId = id;
      this.pmBlockName = name;
      this.$refs["create-pm-block-modal"].show();
    },
    showAddToProjectModal(name, id) {
      this.processId = id;
      this.assetName = name;
      this.assetType = "process";
      this.$refs["add-to-project-modal"].show();
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
    formatOwner(user) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": user,
          "hide-name": false,
        },
      };
    },
    formatCategory(categories) {
      return categories.map(item => item.name).join(', ');
    },
    fetch() {
      Vue.nextTick(() => {

        if (this.cancelToken) {
            this.cancelToken();
            this.cancelToken = null;
          }
          const CancelToken = ProcessMaker.apiClient.CancelToken;

        this.loading = true;
        this.apiDataLoading = true;
        //change method sort by user
        this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
        //change method sort by slot name
        this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

        let url =
            this.status === null || this.status === "" || this.status === undefined
                ? "processes?"
                : "processes?status=" + this.status + "&";

        let pmql = "";
        if (this.pmql !== undefined) {
            pmql = this.pmql;
        }

        let filter = this.filter;

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = "";
          }
        }

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            url +
            "page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&pmql=" +
            encodeURIComponent(pmql) +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=categories,category,user" +
            "&with=events",
            {
              cancelToken: new CancelToken(c => {
                this.cancelToken = c;
              }),
            }
          )
          .then(response => {
            const data = this.addWarningMessages(response.data);
            this.data = this.transform(data);
            this.apiDataLoading = false;
            this.apiNoResults = false;
            this.loading = false;
          }).catch(error => {
            if (error.code === "ERR_CANCELED") {
              return;
            }
            window.ProcessMaker.alert(error.response.data.message, "danger");
            this.data = [];
          });
      });
    },
    addWarningMessages(data) {
      data.data = data.data.map(process => {
        process.warningMessages = [];
        if (!process.manager_id) {
          process.warningMessages.push(this.$t('Process Manager not configured.'));
        }
        if (process.warnings) {
          process.warningMessages.push(this.$t('BPMN validation issues. Request cannot be started.'));
        }
        return process;
      });
      return data;
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
        /<(?!b|\/b|br|img|a|input|hr|link|meta|time|button|select|textarea|datalist|progress|meter|span)[^>]*>/gi,
        "",
      );
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    changePage(page) {
      this.page = page;
      this.fetch();
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

  .processes-table-card {
    padding: 0;
  }

</style>
