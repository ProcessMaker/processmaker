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

            <template slot="requestName" slot-scope="props">
                <b-link @click="onAction('showRequestSummary', props.rowData, props.rowIndex)">
                    {{props.rowData.process.name}}
                </b-link>
            </template>

            <template slot="assignee" slot-scope="props">
                <avatar-image class="d-inline-flex pull-left align-items-center" size="25" class-image="m-1"
                              :input-data="props.rowData.avatarData"></avatar-image>
            </template>

            <template slot="actions" slot-scope="props">
                <div class="actions">
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
    import AvatarImage from "../../components/AvatarImage"
    import moment from "moment";

    Vue.component('avatar-image', AvatarImage);

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
                    },
                ],
                fields: [
                    {
                        title: "ID",
                        name: "id",
                        sortField: "id"
                    },
                    {
                        title: "TASK",
                        name: "__slot:name",
                        field: "element_name",
                        sortField: "element_name"
                    },
                    {
                        title: "REQUEST",
                        name: "__slot:requestName",
                        field: "request",
                        sortField: "request.name"
                    },
                    {
                        title: "ASSIGNEE",
                        name: "__slot:assignee",
                        field: "user",
                        sortField: "user.lastname"
                    },
                    {
                        title: "DUE DATE",
                        name: "due_at",
                        callback: this.formatDueDate,
                        sortField: "due_at"
                    }
                ]
            };
        },
        mounted: function mounted() {
            let params = new URL(document.location).searchParams;
            let successRouting = params.get("successfulRouting") === "true";
            if (successRouting) {
                ProcessMaker.alert("The request was completed successfully.", "success");
            }
        },
        methods: {
            onAction(action, rowData, index) {
                if (action === "edit") {
                    let link = "/tasks/" + rowData.id + "/edit";
                    window.location = link;
                }

                if (action === "showRequestSummary") {
                    let link = "/requests/" + rowData.id;
                    window.location = link;
                }
            },
            formatDueDate(value) {
                let dueDate = moment(value);
                let now = moment();
                let diff = dueDate.diff(now, "hours");
                let color =
                    diff < 0 ? "text-danger" : diff <= 1 ? "text-warning" : "text-primary";
                return '<span class="' + color + '">' +   dueDate.format('MM/DD/YYYY HH:MM') +
                "</span>";
            },
            getTaskStatus() {
                let path = new URL(location.href);
                let status = path.searchParams.get('status');
                return ((status === null) ? 'ACTIVE' : status);
            },

            getSortParam: function() {
                if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
                    return "&order_by=" + this.sortOrder[0].sortField +
                        "&order_direction=" + this.sortOrder[0].direction;
                } else {
                    return '';
                }
            },

            transform(data) {
                // Clean up fields for meta pagination so vue table pagination can understand
                data.meta.last_page = data.meta.total_pages;
                data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
                data.meta.to = data.meta.from + data.meta.count;

                //load data for participants
                for (let record of data.data) {
                    record['avatarData'] = [{
                        id: record['user']['id'],
                        src: record['user']['avatar'],
                        title:record['user']['fullname'],
                        name:record['user']['fullname'],
                        initials: record['user']['firstname'][0] + record['user']['lastname'][0]
                    }]

                }
                return data;
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
                        "tasks?page=" +
                        this.page +
                        "&include=process,processRequest,processRequest.user,user" +
                        "&status=" + this.getTaskStatus() +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        this.getSortParam()
                        ,{
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

