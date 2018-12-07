<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <div class="popout">
                        <b-btn variant="action" @click="onEdit(props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                        <b-btn variant="action" @click="onDelete( props.rowData, props.rowIndex)" v-b-tooltip.hover
                               title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Group" plural="Groups" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>

<script>
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter"],
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
                        title: "Name",
                        name: "name",
                        sortField: "Name"
                    },
                    {
                        title: "Description",
                        name: "description",
                        sortField: "description"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status",
                        callback: this.formatStatus
                    },
                    {
                        title: "Members",
                        name: "group_members_count",
                        sortField: "group_members_count"
                    },
                    {
                        title: "Created At",
                        name: "created_at",
                        sortField: "created_at",
                        callback: 'formatDate'
                    },
                    {
                        title: "Updated At",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: 'formatDate'
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },
        methods: {
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {
                    'active': 'text-success',
                    'inactive': 'text-danger',
                    'draft': 'text-warning',
                    'archived': 'text-info'
                };
                return '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ' + status.charAt(0).toUpperCase() + status.slice(1);
            },
            onEdit(data, index) {
                window.location = "/admin/groups/" + data.id + "/edit";
            },
            onDelete(data, index) {
                let that = this;
                ProcessMaker.confirmModal('Caution!', '<b>Are you sure to delete the group </b>' + data.name + '?', '', function () {
                    ProcessMaker.apiClient
                        .delete('groups/' + data.id)
                        .then(response => {
                            ProcessMaker.alert('Group successfully eliminated', 'success');
                            that.fetch();
                        });
                });
            },
            onAction(action, data, index) {
                switch (action) {
                    case "users-item":
                        //todo
                        break;
                    case "permissions-item":
                        //todo
                        break;
                }
            },
            fetch() {
                this.loading = true;
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "groups?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        this.orderBy +
                        "&order_direction=" +
                        this.orderDirection +
                        "&include=membersCount"
                    )
                    .then(response => {
                        this.data = this.transform(response.data);
                        this.loading = false;
                    });
            }
        }
    };
</script>

<style lang="scss" scoped>
    /deep/ th#_total_users {
        width: 150px;
        text-align: center;
    }

    /deep/ .vuetable-th-status {
        min-width: 90px;
    }

    /deep/ .vuetable-th-members_count {
        min-width: 90px;
    }
</style>
