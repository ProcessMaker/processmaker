<template>
  <div v-if="!existData" class="container">
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
        <p>{{ $t("You are not part of a project yet") }}</p>
        <b-link href="#">
          {{ $t("Create a Project") }}
        </b-link>
      </div>
    </div>
  </div>
  <div v-else class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/\/processes\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body processes-table-card"
      data-cy="processes-table"
    >
      <vuetable
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
        <template slot="title" slot-scope="props">
          <a :href="'/designer/projects/' + props.rowData.id" v-uni-id="props.rowData.id.toString()">{{props.rowData.title}}
          </a>
        </template>

        <template slot="actions" slot-scope="props">
          <ellipsis-menu
            @navigate="onNavigate"
            :actions="actions"
            :data="props.rowData"
            :divider="true"
            data-cy="project-list-ellipsis"
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
  props: ["status", "permission", "isDocumenterInstalled", "currentUserId"],
  data() {
    return {
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
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
      actions: [
        { value: "open-item", content: "Open", link: true, href: "/designer/projects/{{id}}", icon: "fas fa-sign-in-alt"},
        { value: "remove-item", content: "Delete", icon: "fas fa-trash"},
        { value: "export-item", content: "Export", link: true, href: "/designer/projects/{{id}}/export", icon: "fas fa-file-export"},
      ],
      configs: "",
      existData: false,
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-process", (val) => {
      this.fetch();
    });
  },
  methods: {
    fetch() {
      this.loading = true;
      this.apiDataLoading = true;
      this.orderBy = this.orderBy === "__slot:updated_at" ? "updated_at" : this.orderBy;

      const url = "projects?";
      const status = this.status ? this.status : "all";

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `${url}
          status=
          ${status}
          &page=1
          &per_page=10
          &order_by=
          ${this.orderBy}
          &order_direction=
          ${this.orderDirection}`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.configs = response.data.data;
          this.apiDataLoading = false;
          this.loading = false;
          if (this.data.data.length) {
            this.existData = true;
          }
        })
        .catch((error) => {
          if (error.code === "ERR_CANCELED") {
            return;
          }
          window.ProcessMaker.alert(error.response.data.message, "danger");
          this.data = [];
        });
    },
    onNavigate(action, data) {
      switch (action.value) {
        case "remove-item":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
              this.$t("Are you sure you want to delete the project ") +
              "'" + data.title + "'" +
              "?",
              "",
              () => {
                window.ProcessMaker.apiClient
                .delete(`projects/${data.id}`)
                .then(response => {
                  ProcessMaker.alert(
                    this.$t("The project was deleted."),
                    "success"
                  );
                  this.$emit("reload");
                  this.$refs.pagination.loadPage(1);
                }).catch(error => {
                  ProcessMaker.alert(
                    this.$t(error.response?.message),
                    "danger"
                  );
                });
              }
            );
          break;
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
</style>
