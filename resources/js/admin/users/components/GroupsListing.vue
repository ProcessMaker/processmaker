<template>
    <div class="data-table">
        <div>
            <vuetable
                    :dataManager="dataManager"
                    :sortOrder="sortOrder"
                    :css="css"
                    :api-mode="false"
                    @vuetable:pagination-data="onPaginationData"
                    :fields="fields"
                    :data="data"
                    data-path="data"
                    pagination-path="meta">
                <template slot="actions" slot-scope="props">
                    <div class="actions">
                        <div class="popout">
                            <b-btn
                                    variant="link"
                                    @click="deleteMembership(props.rowData)"
                                    v-b-tooltip.hover
                                    :title="__('Remove from Group')"
                            >
                                <i class="fas fa-minus-circle fa-lg fa-fw"></i>
                            </b-btn>
                        </div>
                    </div>
                </template>
            </vuetable>

            <pagination
                    single="Group"
                    plural="Groups"
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
            title: __("Name"),
            name: "name",
            sortField: "name"
          },
          {
            title: __("Description"),
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
    __(variable) {
      return __(variable);
    },
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
          __("Caution!"),
          __("Are you sure you want to remove the user from the group ") + item.name + __("?"),
          "",
          () => {
            ProcessMaker.apiClient.delete("group_members/" + item.id).then(response => {
              ProcessMaker.alert(__("The user was removed from the group."), "success");
              this.fetch();
            });
          }
        );
      }
    }
  }

</script>
