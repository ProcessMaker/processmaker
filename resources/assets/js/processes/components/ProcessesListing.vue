<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="name" slot-scope="props">
                <b-btn variant="link text-capitalize" @click="onAction('edit-designer', props.rowData, props.rowIndex)">
                    {{props.rowData.name}}
                </b-btn>
            </template>

            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
                        <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                        <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Process" plural="Processs" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter", "uid"],
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
                        title: 'Process',
                        name: "__slot:name",
                        field: "name",
                        sortField: "name",
                    },
                    {
                        title: "Category",
                        name: "category",
                        sortField: "category"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status",
                        callback: this.formatStatus
                    },
                    {
                        title: "Modified By",
                        name: "user",
                        sortField: "user",
                    },
                    {
                        title: "Modified",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: this.formatDate
                    },
                    {
                        title: "Created",
                        name: "created_at",
                        sortField: "created_at",
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
            activateBtnCssClass(data) {
                var showPowerOn = (data.status === 'INACTIVE') ? true : false;
                var showPowerOff = (data.status === 'ACTIVE') ? true : false;
                return {
                    'fa-toggle-off': showPowerOff,
                    'fa-toggle-on': showPowerOn
                };
            },
            activateBtnTitle(data) {
                return (data.status === 'ACTIVE') ? 'Deactivate' : 'Activate'
            },
            onAction(actionType, data, index) {
                if (actionType === 'edit-designer') {
                    window.open('/designer/' + data.uid,'_self');
                }

                if (actionType === 'toggle-status') {
                    this.loading = true;
                    ProcessMaker.apiClient
                        .put('/processes/' + data.uid, {
                            status: (data.status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE'
                        })
                        .then(response => {
                            this.loading = false;
                            document.location.reload();
                        });
                }

                if (actionType === 'edit-item') {
                    this.$emit('edit', data.uid);
                }

                if (actionType === 'remove-item') {
                    let that = this;
                    ProcessMaker.confirmModal('Caution!', '<b>Are you sure to delete the category </b>' + data.name + '?', '', function () {
                        ProcessMaker.apiClient
                            .delete('processes/' + data.uid)
                            .then(response => {
                                ProcessMaker.alert('Process successfully eliminated', 'success');
                                that.fetch();
                            })
                    });
                }
            },
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {
                    'active': 'text-success',
                    'inactive': 'text-danger',
                    'draft': 'text-warning',
                    'archived': 'text-info'
                };
                let response = '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
                status = status.charAt(0).toUpperCase() + status.slice(1);
                return response + status;
            },
            fetch() {
                this.loading = true;
                if (this.cancelToken) {
                    this.cancelToken();
                    this.cancelToken = null;
                }
                const CancelToken = ProcessMaker.apiClient.CancelToken;

                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "processes/" +
                        "?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        (this.orderBy === '__slot:name' ? 'id' : this.orderBy) +
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
                    });
            }
        },

        computed: {}
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
</style>
