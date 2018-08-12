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
                        <b-btn variant="action" @click="onAction('toggle-status', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover :title='activateBtnTitle(props.rowData)'><i class="fas"
                                                                                             v-bind:class='activateBtnCssClass(props.rowData)'></i>
                        </b-btn>
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
                orderBy: "code",

                sortOrder: [
                    {
                        field: "name",
                        sortField: "name",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        name: "__checkbox"
                    },
                    {
                        title: "Process",
                        name: "name",
                        sortField: "name"
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
            activateBtnCssClass (data) {
                var showPowerOn = (data.status === 'INACTIVE') ? true : false;
                var showPowerOff = (data.status === 'ACTIVE') ? true : false;
                return {
                    'fa-toggle-off': showPowerOff,
                    'fa-toggle-on': showPowerOn
                };
            },
            activateBtnTitle (data) {
                return (data.status === 'ACTIVE') ? 'Deactivate' : 'Activate'
            },
            onAction (actionType, data, index) {
                if (actionType === 'edit-item') {
                    window.open('/designer/' + data.uid);
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
            },
            formatStatus(value) {
                value = value.toLowerCase();
                console.log(value);
                let colorValue = '';
                console.log(colorValue);
                    if (value === 'active'){
                        colorValue = 'text-success'
                    }
                    if (value === 'inactive'){
                        colorValue = 'text-danger'
                    }
                    if (value === 'draft'){
                        colorValue = 'text-warning'
                    }
                    if (value === 'archived'){
                        colorValue = 'text-info'
                    }
                let response = '<i class="fas fa-circle ' + colorValue + '"></i> ';
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
                        "processes/" +
                        "?page=" +
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

    /deep/ i.fa-circle {
        &.active {
            color: green;
        }
        &.inactive {
            color: red;
        }
    }
</style>

