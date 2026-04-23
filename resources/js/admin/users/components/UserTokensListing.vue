<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/users\/.+\/tokens\?.+/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body table-card"
    >
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        :no-data-template="$t('No Data Available')"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-b-tooltip.hover
                variant="link"
                :title="$t('Delete Token')"
                @click="deleteToken(props.rowData)"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>

      <pagination
        ref="pagination"
        :single="$t('Token')"
        :plural="$t('Tokens')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["user_id"],
  data() {
    return {
      orderBy: "name",
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          callback: "shorten",
        },
        {
          title: () => this.$t("Created At"),
          name: "created_at",
        },
        {
          title: () => this.$t("Expires At"),
          name: "expires_at",
        },
        {
          title: () => "",
          name: "__slot:actions",
        },
      ],
    };
  },
  methods: {
    fetch() {
      this.loading = true;
      ProcessMaker.apiClient
        .get(
          `users/${
            this.user_id
          }/tokens?page=${
            this.page
          }&per_page=${
            this.perPage
          }&order_by=${
            this.orderBy
          }&order_direction=${
            this.orderDirection}`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    deleteToken(row) {
      const tokenId = row.id;
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to delete the token ") + tokenId.substr(0, 7)
          + this.$t("? Any services using it will no longer have access."),
        "",
        () => {
          ProcessMaker.apiClient({
            method: "DELETE",
            url: `/users/${this.user_id}/tokens/${tokenId}`,
          })
            .then((result) => {
              this.fetch();
              this.newToken = null;
            });
        },
      );
    },
    shorten(id) {
      return id.substring(0, 7);
    },
  },
};

</script>

<style lang="scss" scoped>

</style>
