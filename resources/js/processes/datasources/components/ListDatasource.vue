<template>
  <div class="data-table">
    <data-loading
      :for="/datasources/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        @vuetable:pagination-data="onPaginationData"
        pagination-path="meta"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        :noDataTemplate="$t('No Data Available')"
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="edit(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                v-if="permission.includes('edit-datasources')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doDelete(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Remove')"
                v-if="permission.includes('delete-datasources')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Data Source')"
        :plural="$t('Data Sources')"
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
    props: ["filter", "permission"],
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
            name: "name",
            sortField: "name"
          },
          {
            title: () => this.$t("Description"),
            name: "description",
            sortField: "description"
          },
          {
            title: this.$t("Category"),
            name: "category.name",
            sortField: "category.name"
          },
          {
            title: () => this.$t('Modified'),
            name: "updated_at",
            sortField: "updated_at",
            callback: "formatDate"
          },
          {
            title: () => this.$t('Created'),
            name: "created_at",
            sortField: "created_at",
            callback: "formatDate"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    methods: {
      fetch() {
        this.loading = true;
        // Load from our api client
        ProcessMaker.apiClient
          .get("datasources?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=category"
          )
          .then(response => {
            this.data = this.transform(response.data);
            this.loading = false;
          });
      },
      edit(row) {
        window.location.href  = "/designer/datasources/" + row.id + '/edit'
      },
      doDelete(item) {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          this.$t("Are you sure you want to delete Data Source") + ' ' +
          item.name +
          this.$t("?"),
          "",
          () => {
            ProcessMaker.apiClient
              .delete("datasources/" + item.id)
              .then(() => {
                ProcessMaker.alert(this.$t('The Data Source was deleted.'), 'success');
                this.fetch();
              });
          }
        );
      }
    }
  };
</script>
