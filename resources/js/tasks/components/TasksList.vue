<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
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
                orderBy: "task_due_date",

                sortOrder: [
                    {
                        field: "task_due_date",
                        sortField: "task_due_date",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        title: "TITLE",
                        name: "",
                        callback: this.formatTitle
                    },
                    {
                        title: "PROCESS",
                        name: "application.process.name"
                    },
                    {
                        title: "ASSIGNED TO",
                        name: "",
                        callback: this.formatUserName
                    },
                    {
                        title: "CREATED BY",
                        name: "",
                        callback: this.formatCreatorName
                    },
                    {
                        title: "DUE",
                        name: "task_due_date",
                        sortField: "task_due_date",
                        callback: this.formatDue
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
            formatCreatorName(token) {
                return (token.application.creator.avatar
                    ? this.createImg({'src': token.application.creator.avatar, 'class': 'rounded-user'})
                    : '<i class="fa fa-user rounded-user"></i>')
                  + '<span>' + token.application.creator.fullname + '</span>';
            },
            formatUserName(token) {
                return (token.application.creator.avatar
                    ? this.createImg({'src': token.user.avatar, 'class': 'rounded-user'})
                    : '<i class="fa fa-user rounded-user"></i>')
                  + '<span>' + token.user.fullname + '</span>';
            },
            formatTitle(token) {
                let link = '/tasks/'
                        + token.definition.id + '/'
                        + token.application.process.uid + '/'
                        + token.application.uid + '/'
                        + token.uid;
                return this.createLink({'href': link}, token.definition.name);
            },
            createLink(properties, name) {
                let container = document.createElement('div');
                let link = document.createElement('a');
                for (let property in properties) {
                    link.setAttribute(property, properties[property]);
                }
                link.innerText = name;
                container.appendChild(link);
                return container.innerHTML;
            },
            createImg(properties, name) {
                let container = document.createElement('div');
                let node = document.createElement('img');
                for (let property in properties) {
                    node.setAttribute(property, properties[property]);
                }
                container.appendChild(node);
                return container.innerHTML;
            },
            formatDue(value) {
                let duedate = moment(value);
                let now = moment();
                let diff = duedate.diff(now, 'hours');
                let color = diff < 0 ? 'text-danger' : (diff <= 48 ? 'text-warning' : 'text-primary');
                let circle = '<i class="fas fa-circle ' + color + ' small"></i> ';
                return circle + moment(value).fromNow();
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
                        "&include=application.creator" +
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
    .rounded-user {
        border-radius: 50%!important;
        height: 1.5em;
        margin-right: 0.5em;
    }
</style>

