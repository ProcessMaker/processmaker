<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/clients/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body table-card"
    >
      <vuetable
        ref="vuetable"
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :no-data-template="$t('No Data Available')"
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
          <ellipsis-menu
            :actions="actions"
            :data="props.rowData"
            :permission="permission"
            :divider="true"
            @navigate="onNavigate"
          />
        </template>
        <template
          slot="secret"
          slot-scope="props"
        >
          <b-btn
            v-b-tooltip.hover
            v-uni-aria-describedby="props.rowData.id.toString()"
            variant="link"
            class="copylink"
            :title="$t('Copy Client Secret To Clipboard')"
            @click="copySecret(props.rowData.secret)"
          >
            <i class="fas fa-clipboard fa-lg fa-fw" />
          </b-btn>
          {{ props.rowData.secret.substr(0, 10) }}...
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Auth Client')"
        :plural="$t('Auth Clients')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
      <textarea
        ref="copytext"
        class="copytext"
      />
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      copytext: "",
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      actions: [
        {
          value: "edit-item", content: "Edit Auth Client", icon: "fas fa-pen-square", ariaDescribedBy: "data.id",
        },
        {
          value: "delete-item", content: "Delete Auth Client", icon: "fas fa-trash-alt", ariaDescribedBy: "data.id",
        },
      ],
      fields: [
        {
          title: () => this.$t("Client ID"),
          name: "id",
        },
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
        },
        {
          title: () => this.$t("Redirect"),
          name: "redirect",
          callback(val) {
            return `${val.substr(0, 20)}...`;
          },
        },
        {
          title: () => this.$t("Client Secret"),
          name: "__slot:secret",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  methods: {
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get("/oauth/clients", { baseURL: "/" })
        .then((response) => {
          this.data = this.transform(response.data.data);
          this.loading = false;
        });
    },
    transform(data) {
      if (this.filter) {
        // Manual filter
        data = data.filter((item) => (
          item.name.toLowerCase().indexOf(this.filter.toLowerCase()) > -1
            || item.redirect.toLowerCase().indexOf(this.filter.toLowerCase()) > -1
            || item.secret.toLowerCase().indexOf(this.filter.toLowerCase()) > -1
        ));
      }

      // Pagination
      const meta = {};
      if (parseInt(this.perPage) >= data.length) {
        this.page = 1;
      }

      meta.total = data.length;
      meta.per_page = parseInt(this.perPage);
      meta.total_pages = Math.floor(meta.total / meta.per_page) + 1;
      if (this.page > meta.total_pages) {
        this.page = meta.total_pages;
      }
      meta.current_page = this.page;
      meta.from = (meta.current_page - 1) * meta.per_page;
      meta.last_page = meta.total_pages;
      meta.to = meta.from + meta.per_page;
      if (meta.to > meta.total) {
        meta.to = meta.total;
      }
      const rows = data.slice(meta.from, meta.to);
      meta.count = rows.length;

      this.$refs.pagination.tablePagination = meta;
      return rows;
    },
    changePerPage(value) {
      this.perPage = value;
      if (this.page * value > this.$refs.pagination.tablePagination.total) {
        this.page = Math.floor(this.$refs.pagination.tablePagination.total / value) + 1;
      }
      this.fetch();
    },
    onPageChange(page) {
      if (page == "next") {
        this.page += 1;
      } else if (page == "prev") {
        this.page -= 1;
      } else {
        this.page = page;
      }
      if (this.page <= 0) {
        this.page = 1;
      }
      const meta = this.$refs.pagination.tablePagination;
      if (this.page > meta.last_page) {
        this.page = meta.last_page;
      }
      this.fetch();
    },
    copySecret(secret) {
      this.$refs.copytext.value = secret;
      this.$refs.copytext.select();
      document.execCommand("copy");
    },
    onNavigate(action, data) {
      switch (action.value) {
        case "edit-item":
          this.$emit("edit", { ...data });
          break;
        case "delete-item":
          this.doDelete(data);
          break;
      }
    },
    doDelete(item) {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        `${this.$t("Are you sure you want to delete the auth client")
        } ${
          item.name
        }${this.$t("?")}`,
        "",
        () => {
          ProcessMaker.apiClient
            .delete(`/oauth/clients/${item.id}`, { baseURL: "/" })
            .then(() => {
              ProcessMaker.alert(
                this.$t("The auth client was deleted."),
                "success",
              );
              this.fetch();
            });
        },
      );
    },
  },
};
</script>

<style>
.copytext {
  position: absolute;
  left: -1000px;
  top: -1000px;
}

.copylink {
  padding: 0;
}
</style>
