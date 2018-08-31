<template>
    <div>
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="ids" slot-scope="props">
                <div class="actions">
                    <b-btn variant="link" @click="openRequest(props.rowData, props.rowIndex)">{{props.rowData.id}}
                    </b-btn>
                </div>
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
    import Vuetable from "vuetable-2/src/components/Vuetable";
    import Pagination from "../../components/common/Pagination";
    import datatableMixin from "../../components/common/mixins/datatable";
    import moment from "moment"

    export default {
        mixins: [datatableMixin],
        props: ["filter"],
        data() {
            return {
                orderBy: "id",
                sortOrder: [
                    {
                        field: "id",
                        sortField: "id",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        name: "__slot:ids",
                        title: "id",
                        field: 'id',
                        sortField: "id",
                        width: '50px'
                    },
                    {
                        title: "Process",
                        name: "process.name",
                        sortField: "process.name"
                    },
                    {
                        title: "Assigned to",
                        name: "delegations",
                        callback: this.assignedTo
                    },
                    {
                        title: "Due date",
                        name: "delegations",
                        callback: this.formatDueDate
                    },
                    {
                        title: "Created on",
                        name: "APP_CREATE_DATE",
                        sortField: "APP_CREATE_DATE",
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
                window.open('/requests/' + data.uid + '/status','_self');
            },
            formatUid(id) {
                return id;
            },
            assignedTo(delegations) {
                let assignedTo = '';
                if (!delegations) return assignedTo;
                let that = this;
                let count = 0;
                delegations.forEach(function (delegation, key) {
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
                        (this.orderBy === '__slot:ids' ? 'id' : this.orderBy) +
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
    /deep/ .circle {
        border-radius: 50px/50px;
        height: 1.5em;
        background-color: #6c757d;
        display: inline-table;
        margin-right: 0.5em;
    }

    /deep/ .rounded-user {
        border-radius: 50% !important;
        height: 1.5em;
        margin-right: 0.5em;
    }

    /deep/ .initials {
        color: white;
        padding: 3px;
        font-size: 10px;
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
