<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">

            <template slot="name" slot-scope="props">
                <b-link @click="onAction('edit', props.rowData, props.rowIndex)">
                    {{props.rowData.element_name}}
                </b-link>
            </template>

            <template slot="participants" slot-scope="props">
                <avatar-image class="d-inline-flex pull-left align-items-center" size="25" class-image="m-1"
                              :input-data="props.rowData.participants"></avatar-image>
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

    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";
    import moment from "moment";

    export default {
        mixins: [datatableMixin],
        props: ["processRequestId", "status"],
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
                        title: "ASSIGNED",
                        name: "__slot:participants",
                        field: "participants",
                        sortField: "previousUser.lastname"
                    },
                    {
                        title: "DUE DATE",
                        name: "due_at",
                        callback: this.formatDueDate,
                        sortField: "due_at"
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },
        mounted: function mounted() {

        },
        methods: {
            onAction(action, rowData, index) {
                switch (action) {
                    case 'edit':
                        window.location = '/tasks/' + rowData.id + '/edit';
                        break
                }
            },
            formatDueDate(value) {
                let duedate = moment(value);
                let now = moment();
                let diff = duedate.diff(now, "hours");
                let color =
                    diff < 0 ? "text-danger" : diff <= 1 ? "text-warning" : "text-primary";
                return '<span class="' + color + '">' + value + "</span>";
            },
            formatDate(value) {
                return moment(value).fromNow();
            },
            transform(data) {
                // Clean up fields for meta pagination so vue table pagination can understand
                data.meta.last_page = data.meta.total_pages;
                data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
                data.meta.to = data.meta.from + data.meta.count;
                //load data for participants
                for (let record of data.data) {
                    record['participants'] = [{
                        src: record['previousUser']['avatar'],
                        name: record['previousUser']['fullname'],
                        initials: record['previousUser']['firstname'][0] + record['previousUser']['lastname'][0]
                    }]
                }
                return data;
            },
            fetch() {
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "tasks?page=" +
                        this.page +
                        "&include=process,processRequest,processRequest.user" +
                        "&process_request_id=" +
                        this.processRequestId +
                        "&status=" +
                        this.status +
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
