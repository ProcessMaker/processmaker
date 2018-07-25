<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
                        <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                        <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                        <b-btn variant="action" @click="onAction('permissions-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Permissions"><i class="fas fa-user-lock"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Task" plural="Tasks" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>

<script>
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter", "uid"],
        data() {
            return {
                orderBy: "code",

                sortOrder: [
                    {
                        field: "title",
                        sortField: "title",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        name: "__checkbox"
                    },
                    {
                        title: "Code",
                        name: "uid",
                        sortField: "uid"
                    },
                    {
                        title: "Title",
                        name: "title",
                        sortField: "title"
                    },
                    {
                        title: "Description",
                        name: "description",
                        sortField: "description"
                    },
                    {
                        title: "Type",
                        name: "type",
                        sortField: "type",
                    },
                    {
                        title: "Created At",
                        name: "created_at",
                        sortField: "created_at",
                        callback: this.formatDate
                    },
                    {
                        title: "Updated At",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: this.formatDate
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },
        methods: {
            formatStatus(value) {
                value = value.toLowerCase();
                let response = '<i class="fas fa-circle ' + value + '"></i> ';
                value = value.charAt(0).toUpperCase() + value.slice(1);
                return response + value;
            },
            fetch() {
                this.loading = true;
                if (this.cancelToken) {
                    this.cancelToken();
                    this.cancelToken = null;
                }
                const CancelToken = ProcessMaker.apiClient.CancelToken;

                //@todo change the method to obtain ID of the process.
                var path = location.pathname.split('/');

                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "process/" +
                        this.uid +
                        "/tasks?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        this.orderBy +
                        "&order_direction=" +
                        this.orderDirection,
                        {
                            cancelToken: new CancelToken(c => {
                                this.cancelToken = c;
                            })
                        }
                    )
                    .then(response => {
                        this.data = this.transform(response.data);
                        this.loading = false;
                    })
                    .catch(error => {
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

    /deep/ th#_description {
        width: 250px;
    }

    /deep/ i.fa-circle {
        &.active {
            color: green;
        }
        &.inactive {
            color: red;
        }
    }
</style>

