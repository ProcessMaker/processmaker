<template>
    <div>
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="uuids" slot-scope="props">
                <b-btn variant="link" size="sm" @click="openRequest(props.rowData, props.rowIndex)">
                    {{props.rowData.uuid}}
                </b-btn>
            </template>

            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="popout">
                        <b-btn variant="action" @click="openRequest(props.rowData, props.rowIndex)" v-b-tooltip.hover
                               title="Open">
                            <i class="fas fa-folder-open"></i>
                        </b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Request" plural="Requests" :perPageSelectEnabled="true" @changePerPage="changePerPage"
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
                orderBy: "uuid",
                sortOrder: [
                    {
                        field: "uuid",
                        sortField: "uuid",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        name: "__slot:uuids",
                        title: "uuid",
                        field: 'uuid',
                        sortField: "uuid",
                        width: '50px'
                    },
                    {
                        title: "Process",
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status"
                    },
                    {
                        title: "Stage",
                        name: "stage",
                        sortField: "stage"
                    },
                    {
                        title: "Participants",
                        name: "user",
                        sortField: "user",
                        callback: this.assignedTo
                    },
                    {
                        title: "Started",
                        name: "start_at",
                        sortField: "start_at",
                        callback: this.formatDate
                    },
                    {
                        title: "Completed",
                        name: "start_at",
                        sortField: "start_at",
                        callback: this.formatDate
                    },
                    {
                        title: "Duration",
                        name: "start_at",
                        sortField: "start_at",
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
            openRequest(data, index) {
                window.open('/requests/' + data.uuid + '/status', '_self');
            },
            formatUid(id) {
                return id;
            },
            assignedTo(delegations) {
                let assignedTo = '';
                if (!delegations) return assignedTo;
                let that = this;
                let count = 0;
                let usedAvatar = [];
                delegations.forEach(function (delegation, key) {

                    if (usedAvatar.includes(delegation.user.uid) === false) {

                        usedAvatar.push(delegation.user.uid);

                        if (key <= 4) {
                            let user = delegation.user;
                            assignedTo += user.avatar ? that.createImg({
                                    'src': user.avatar,
                                    'class': 'rounded-user',
                                    'title': user.fullname
                                })
                                : '<div class="circle"><span class="initials" title="' + user.fullname + '">'
                                + user.firstname[0].toUpperCase() + user.lastname[0].toUpperCase() + '</span></div>';
                        } else {
                            count++;
                        }

                    }

                });
                if (count) {
                    assignedTo += '<div class="circle"><span class="initials">+' + count + '</span></div>';
                }
                return assignedTo;
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
            formatDateWithDot(value) {
                if (!value) {
                    return '';
                }
                let duedate = moment(value);
                let now = moment();
                let diff = duedate.diff(now, 'hours');
                let color = diff < 0 ? 'text-danger' : (diff <= 48 ? 'text-warning' : 'text-primary');
                return '<i class="fas fa-circle ' + color + '"></i> ' + duedate.format('YYYY-MM-DD hh:mm');
            },
            formatDate(value) {
                let date = moment(value);
                return date.format('YYYY-MM-DD hh:mm');
            },
            formatDueDate(delegations) {
                let overdue = false;
                let risk = false;
                if (delegations) {
                    delegations.forEach(function (delegation) {
                        if (delegation.delay && delegation.delay.toUpperCase() === 'OVERDUE') {
                            overdue = true;
                        } else if (delegation.delay && delegation.delay.toUpperCase() === 'AT_RISK') {
                            risk = true;
                        }
                    });
                }
                let status = overdue ? 'OVERDUE' : (risk ? 'AT RISK' : 'ON TIME');
                return ' <span style="text-transform: uppercase; ">' + status + '</span>';
            },
            transform(data) {
                // Clean up fields for meta pagination so vue table pagination can understand
                data.meta.last_page = data.meta.total_pages;
                data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
                data.meta.to = data.meta.from + data.meta.count;
                for (let record of data.data) {
                    record['full_name'] = [record['firstname'], record['lastname']].join(' ');
                }
                return data;
            },
            fetch() {
                this.loading = true;

                //get any additional query string parameters
                let urlParts = window.location.href.split('?');
                let additionalParams = '';
                if (urlParts.length === 2) {
                    additionalParams = '&' + urlParts[1];
                }
                if (this.status) {
                    additionalParams += "&status=" + this.status;
                }

                additionalParams += '&include=user';

                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "requests?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&include=process,delegations,delegations.user" +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        (this.orderBy === '__slot:uuids' ? 'uuid' : this.orderBy) +
                        "&order_direction=" +
                        this.orderDirection +
                        additionalParams
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

    /deep/ .table td {
        vertical-align: middle !important;
        padding: 3px;
    }

    /deep/ .circle {
        border-radius: 100%;
        height: 32px;
        width: 32px;
        background-color: #6c757d;
        display: inline-table;
        margin-right: 0.5em;
        text-align: center;
        vertical-align: middle;
        margin: 0;
    }

    /deep/ .rounded-user {
        border-radius: 50% !important;
        height: 32px;
        margin: 0;
    }

    /deep/ .initials {
        color: white;
        line-height: 2.5em;
    }

    /deep/ i.fa-circle {
        &.active {
            color: green;
        }
        &.inactive {
            color: red;
        }
    }

    /deep/ .vuetable-th-slot-uuids {
        min-width: 100px;
    }

</style>
