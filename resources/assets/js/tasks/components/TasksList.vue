<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
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
                        title: "ID",
                        name: "",
                        sortField: "uid",
                        callback: this.formatUid
                    },
                    {
                        title: "TASK",
                        name: "definition.name",
                        sortField: "title"
                    },
                    {
                        title: "PROCESS",
                        name: "application.process.name",
                        sortField: "application.process.name"
                    },
                    {
                        title: "CREATED BY",
                        name: "application.creator.fullname",
                        sortField: "application.creator.firstname"
                    },
                    {
                        title: "DUE",
                        name: "task_due_date",
                        sortField: "task_due_date",
                        callback: this.formatDue
                    },
                    {
                        title: "DUE DATE",
                        name: "task_due_date",
                        sortField: "task_due_date",
                        callback: this.formatDateWithDot
                    },
                    {
                        title: "LAST MODIFIED",
                        name: "application.APP_UPDATE_DATE",
                        sortField: "application.APP_UPDATE_DATE",
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
            formatUid(token) {
                let short = token.uid.split('-').pop();
                let link = '/tasks/'
                        + token.definition.id + '/'
                        + token.application.process.uid + '/'
                        + token.application.uid + '/'
                        + token.uid;
                return this.createLink({'href': link, 'target': '_blank'}, short);
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
            formatDateWithDot(value) {
                if (!value) {
                    return '';
                }
                let duedate = moment(value);
                let now = moment();
                let diff = duedate.diff(now, 'hours');
                let color = diff < 0 ? 'text-danger' : (diff <= 48 ? 'text-warning' : 'text-primary');
                let circle = '<i class="fas fa-circle ' + color + ' small "></i>';
                return circle + ' ' + duedate.format('YYYY-MM-DD hh:mm');
            },
            formatDue(value) {
                return value ? moment(value).fromNow() : '';
            },
            formatDate(value) {
                return moment(value).format('YYYY-MM-DD hh:mm');
            },
            formatStatus(value) {
                value = value.toLowerCase();
                let response = '<i class="fas fa-circle ' + value + '"></i> ';
                value = value.charAt(0).toUpperCase() + value.slice(1);
                return response + moment(value).format('YYYY-MM-DD hh:mm');
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

