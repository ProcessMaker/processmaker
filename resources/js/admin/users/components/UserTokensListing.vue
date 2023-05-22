<template>
    <div class="data-table">
      <data-loading
              :for=/users\/.+\/tokens\?.+/
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
                    pagination-path="meta">
                <template slot="actions" slot-scope="props">
                  <div class="actions">
                    <div class="popout">
                      <b-btn
                              variant="link"
                              @click="deleteToken(props.rowData)"
                              v-b-tooltip.hover
                              :title="$t('Delete Token')"
                      >
                          <i class="fas fa-trash-alt fa-lg fa-fw"></i>
                      </b-btn>
                    </div>
                  </div>
                </template>
            </vuetable>

            <pagination
                    :single="$t('Token')"
                    :plural="$t('Tokens')"
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
    props: ["user_id"],
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
            title: () => this.$t("ID"),
            name: "id",
            callback: 'shorten'
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
            title: () => '',
            name: "__slot:actions",
          }
        ]
      };
    },
    methods: {
      fetch() {
        this.loading = true;
        ProcessMaker.apiClient
          .get(
            "users/" +
            this.user_id +
            "/tokens?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
          )
          .then(response => {
            this.data = this.transform(response.data);
            this.loading = false;
          });
      },
      deleteToken(row) {
        let tokenId = row.id
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          this.$t("Are you sure you want to delete the token ") + tokenId.substr(0, 7) +
          this.$t("? Any services using it will no longer have access."),
          "",
          () => {
            ProcessMaker.apiClient({
              method: 'DELETE',
              url: '/users/' + this.user_id + '/tokens/' + tokenId,
            })
              .then((result) => {
                this.fetch();
                this.newToken = null;
              })
          }
        );
      },
      shorten(id) {
        return id.substring(0, 7);
      }
    }
  }

</script>

<style lang="scss" scoped>

</style>

