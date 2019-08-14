<template>
    <div class="data-table">
        <div class="card card-body table-card">
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
                                    @click="deleteMembership(props.rowData)"
                                    v-b-tooltip.hover
                                    :title="$t('Remove from Group')"
                            >
                                <i class="fas fa-minus-circle fa-lg fa-fw"></i>
                            </b-btn>
                        </div>
                    </div>
                </template>
            </vuetable>

            <pagination
                    :single="$t('Group')"
                    :plural="$t('Groups')"
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


  export default {
    mixins: [datatableMixin],
    props: ["member_id"],
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
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    methods: {
      fetch() {
        this.loading = true;
        ProcessMaker.apiClient
          .get(
            "group_members?member_id=" +
            this.member_id +
            "&page=" +
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
      deleteMembership(item) {
        ProcessMaker.confirmModal(
          this.$t('Caution!'),
          "<b>" + this.$t('Are you sure you want to delete {{item}}?', {item: item.name}) + "</b>",
          "",
          () => {
            ProcessMaker.apiClient.delete("group_members/" + item.id).then(response => {
              ProcessMaker.alert(this.$t("The user was removed from the group."), "success");
              this.fetch();
            });
          }
        );
      }
    }
  }

</script>
