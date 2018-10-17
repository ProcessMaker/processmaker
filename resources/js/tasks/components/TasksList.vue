<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">

            <template slot="name" slot-scope="props">
                <b-link @click="onAction('edit', props.rowData, props.rowIndex)">
                    {{props.rowData.element_name}}
                </b-link>
            </template>

            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
                        <b-btn variant="action" @click="onAction('edit', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title=""><i class="fas fa-edit"></i></b-btn>
                        <b-btn variant="action" @click="onAction('pause', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title=""><i class="fas fa-pause"></i></b-btn>
                        <b-btn variant="action" @click="onAction('undo', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title=""><i class="fas fa-undo"></i></b-btn>
                    </div>
                </div>
            </template>

        </vuetable>
        <pagination single="Task" plural="Tasks" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";
    import moment from "moment"

    export default {
        mixins: [datatableMixin],
        props: ["filter"],
        data() {
            return {
                orderBy: "due_at",

                sortOrder: [
                    {
                        field: "due_at",
                        sortField: "due_at",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        title: "TASK",
                        name: "__slot:name",
                        field: "element_name",
                        sortField: "element_name"
                    },
                    {
                        title: "PROCESS",
                        name: "process.name",
                        sortField: "process.name",
                    },
                    {
                        title: "SENT BY",
                        name: "previousUser",
                        callback: this.formatName,
                        sortField:"previousUser.lastname"
                    },
                    {
                        title: "DUE DATE",
                        name: "due_at",
                        callback: this.formatDueDate,
                        sortField: "due_at",
                    },
                    {
                        title: "MODIFIED",
                        name: "updated_at",
                        callback: this.formatDate,
                        sortField: "updated_at",
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },
        mounted: function mounted() {
            let params = (new URL(document.location)).searchParams;
            let successRouting = params.get('successfulRouting') === 'true';
            if (successRouting) {
                ProcessMaker.alert('The request was completed successfully.', 'success' )
            }
        },
        methods: {
            onAction(action, rowData, index) {
                if (action === 'edit') {
                    let link = '/tasks/' + rowData.uuid + '/edit';
                    window.location = link;
                }
            },
            formatName(user) {
                if (user === 'undefined' || user === null) {
                    return '';
                }
                let name= '<span>' +
                    user.firstname +
                    ' ' +
                    user.lastname +
                    '</span>';

                let initials = '<span class="avatar-initials-list">' +
                    user.firstname.charAt(0).toUpperCase() +
                    user.lastname.charAt(0).toUpperCase() +
                    '</span>'

                return user.avatar
                    ? '<img class="avatar-image-list avatar-circle-list" src="' + user.avatar + '"> ' + name
                    : '<button type="button" class="avatar-circle-list">'+
                            initials +
                      '</button> ' + name;
            },
            formatDueDate(value) {
                let duedate = moment(value);
                let now = moment();
                let diff = duedate.diff(now, 'hours');
                let color = diff < 0 ? 'text-danger' : (diff <= 1 ? 'text-warning' : 'text-primary');
                return '<span class="' + color +'">' + value + '</span>';
            },
            formatDate(value) {
                return moment(value).fromNow();
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
                        "tasks?page=" +
                        this.page +
                        "&include=process,processRequest,processRequest.user" +
                        "&status=ACTIVE" +
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
        }
    };
</script>

<style lang="scss">
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

