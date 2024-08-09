<template>
  <div
    v-if="data.data.length === 0"
    class="container"
  >
    <div class="content">
      <img
        class="image"
        src="/img/recent_projects.svg"
        alt="recent projects"
      >
      <div class="content-text">
        <span class="title">
          {{ $t("Recent Projects") }}
        </span>
        <span>{{ $t("You are not part of a project yet") }}</span>
        <b-link href="/designer/projects?create=true">
          {{ $t("Create a Project") }}
        </b-link>
      </div>
    </div>
  </div>
  <div
    v-else
    class="data-table"
  >
    <div
      class="loading-my-projects-box"
    >
      <data-loading
        v-show="shouldShowLoader"
        :for="/projects\?page/"
        :empty="$t('No Data Available')"
        :empty-desc="$t('')"
        empty-icon="noData"
      />
    </div>
    <div
      v-show="!shouldShowLoader"
      class="card card-body processes-table-card"
      data-cy="processes-table"
    >
      <vuetable
        id="core-custom-project-table"
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :no-data-template="$t('No Data Available')"
      >
        <template
          slot="title"
          slot-scope="props"
        >
          <a
            v-uni-id="props.rowData.id.toString()"
            :href="`/designer/projects/${props.rowData.id}`"
          >
            {{ props.rowData.title }}
          </a>
        </template>

        <template
          slot="actions"
          slot-scope="props"
        >
          <ellipsis-menu
            :actions="actions"
            :data="props.rowData"
            :divider="true"
            data-cy="project-list-ellipsis"
            @navigate="onNavigate"
          />
        </template>
      </vuetable>
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    EllipsisMenu,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["status", "project"],
  data() {
    return {
      data: {
        data: [],
      },
      orderBy: "updated_at",
      sortOrder: [
        {
          field: "updated_at",
          sortField: "updated_at",
          direction: "desc",
        },
      ],
      fields: [
        {
          title: () => "Name",
          name: "__slot:title",
          sortField: "title",
        },
        {
          title: () => "Modified",
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate",
          width: "auto",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
      actions: [
        {
          value: "open-item",
          content: "Open",
          link: true,
          href: "/designer/projects/{{id}}",
          icon: "fas fa-sign-in-alt",
        },
        {
          value: "remove-item",
          content: "Delete",
          icon: "fas fa-trash",
        },
        {
          value: "export-item",
          content: "Export",
          link: true,
          href: "/designer/projects/{{id}}/export",
          icon: "fas fa-file-export",
        },
      ],
      configs: "",
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-process", (val) => {
      this.fetch();
    });
  },
  methods: {
    fetch(pmql = "") {
      if (this.project) {
        this.loading = true;
        this.apiDataLoading = true;
        this.orderBy = this.orderBy === "__slot:updated_at" ? "updated_at" : this.orderBy;

        const url = "projects?";

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            `${url
            }status=all${
              this.status
            }&page=1`
              + "&per_page=10"
              + `&pmql=${
                encodeURIComponent(pmql)
              }&order_by=${
                this.orderBy
              }&order_direction=${
                this.orderDirection}`,
          )
          .then((response) => {
            this.data = this.transform(response.data);
            this.configs = response.data.data;
            this.apiDataLoading = false;
            this.loading = false;
          })
          .catch((error) => {
            if (error.code === "ERR_CANCELED") {
              return;
            }
            window.ProcessMaker.alert(error.response.data.message, "danger");
            this.data = [];
          });
      }
    },
    onNavigate(action, data) {
      if (action.value === "remove-item") {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          `${this.$t("Are you sure you want to delete the project ")}'${data.title}'?`,
          "",
          () => {
            window.ProcessMaker.apiClient
              .delete(`projects/${data.id}`)
              .then((response) => {
                ProcessMaker.alert(
                  this.$t("The project was deleted."),
                  "success",
                );
                this.fetch();
              }).catch((error) => {
                ProcessMaker.alert(
                  this.$t(error.response?.message),
                  "danger",
                );
              });
          },
        );
      }
    },
    reload() {
      this.$emit("reload");
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
  overflow-y: scroll;
  display: block;
  height: 450px;
}
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  width: 100%;
  height: 450px;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 214px;
  height: 194px;
}
.title {
  color: var(--secondary-800, #44494E);
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -1.28px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.data-table {
  overflow: hidden;
  height: 450px;
  justify-items: center;
  align-items: center;
  justify-content: center;
  width: 100%;
  display: flex;
}
</style>
<style>

#core-custom-project-table tr td.vuetable-slot:last-child {
    text-align: right;
}
</style>
