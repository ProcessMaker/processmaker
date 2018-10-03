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
                               v-b-tooltip.hover :title='activateBtnTitle(props.rowData)'>
                            <i class="fas" v-bind:class='activateBtnCssClass(props.rowData)'></i>
                        </b-btn>
                        <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Process" plural="Processes" :perPageSelectEnabled="true" @changePerPage="changePerPage"
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
                        title: "Process",
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Category",
                        name: "category.name",
                        sortField: "category.name"
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
                        sortField: "user.firstname",
                        callback: this.formatUserName
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
                let showPowerOn = (data.status === 'INACTIVE');
                let showPowerOff = (data.status === 'ACTIVE');
                return {
                    'fa-toggle-off': showPowerOff,
                    'fa-toggle-on': showPowerOn
                };
            },
            activateBtnTitle(data) {
                return (data.status === 'ACTIVE') ? 'Deactivate' : 'Activate'
            },
            onAction(actionType, data, index) {
                if (actionType === 'edit-item') {
                    window.open('/designer/' + data.uuid, '_self');
                }

                if (actionType === 'toggle-status') {
                    this.loading = true;
                    ProcessMaker.apiClient
                        .put('/processes/' + data.uuid, {
                            status: (data.status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE'
                        })
                        .then(response => {
                            this.loading = false;
                            document.location.reload();
                        });
                }

                if (actionType === 'remove-item') {
                    let that = this;
                    ProcessMaker.confirmModal('Caution!', '<b>Are you sure to delete the category </b>' + data.name + '?', '', function () {
                        ProcessMaker.apiClient
                            .delete('processes/' + data.uuid)
                            .then(response => {
                                ProcessMaker.alert('Process successfully eliminated', 'success');
                                that.fetch();
                            })
                    });
                }
            },
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {'active': 'text-success', 'inactive': 'text-danger', 'draft': 'text-warning', 'archived': 'text-info'};
                let response = '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
                status = status.charAt(0).toUpperCase() + status.slice(1);
                return response + status;
            },
            formatUserName(user) {
                return (user.avatar
                    ? this.createImg({'src': user.avatar, 'class': 'rounded-user'})
                    : '<i class="fa fa-user rounded-user"></i>')
                    + '<span>' + user.fullname + '</span>';
            },
            createImg(properties) {
                let container = document.createElement('div');
                let node = document.createElement('img');
                for (let property in properties) {
                    node.setAttribute(property, properties[property]);
                }
                container.appendChild(node);
                return container.innerHTML;
            },
            fetch() {
                this.loading = true;
                //change method sort by user
                this.orderBy = this.orderBy === 'user' ? 'user.firstname' : this.orderBy;
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        'processes' +
                        '?page=' +
                        this.page +
                        '&per_page=' +
                        this.perPage +
                        '&filter=' +
                        this.filter +
                        '&order_by=' +
                        this.orderBy +
                        '&order_direction=' +
                        this.orderDirection +
                        '&include=category,user'
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

    /deep/ .rounded-user {
        border-radius: 50% !important;
        height: 1.5em;
        margin-right: 0.5em;
    }
</style>
