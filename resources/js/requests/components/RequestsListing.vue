<template>
    <div>
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="ids" slot-scope="props">
                <b-link @click="openRequest(props.rowData, props.rowIndex)">
                    {{props.rowData.id}}
                </b-link>
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
                orderBy: "id",
                additionalParams: '',
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
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status"
                    },
                    {
                        title: "Participants",
                        name: "assigned",
                        callback: this.assignedTo
                    },
                    {
                        title: "Started",
                        name: "created_at",
                        sortField: "created_at",
                    },
                    {
                        title: "Completed",
                        name: "completed_at",
                        sortField: "completed_at"
                    },
                ]
            };
        },
        methods: {
            assignedTo(delegations) {
                let assignedTo = '';
                if (!delegations) return assignedTo;
                let count = 0;
                let usedAvatar = [];
                delegations.forEach(function (delegation, key) {

                    if (delegation.user && usedAvatar.includes(delegation.user.id) === false) {

                        usedAvatar.push(delegation.user.id);

                        if (key <= 4) {
                            let user = delegation.user;
                            assignedTo += user.avatar
                                ? '<img class="avatar-image-list avatar-circle-list" src="' + user.avatar + '" title="' + user.fullname + '"> '
                                : '<button type="button" class="avatar-circle-list" title="' + user.fullname + '">' +
                                '<span class="avatar-initials-list">' +
                                user.firstname.charAt(0).toUpperCase() +
                                user.lastname.charAt(0).toUpperCase() +
                                '</span>' +
                                '</button> ';
                        } else {
                            count++;
                        }
                    }
                });
                if (count) {
                    assignedTo += '<button type="button" class="avatar-circle-list"><span class="avatar-initials-list">+' + count + '</span></button>';
                }
                return assignedTo;
            },
            formatStatus(status) {
                let color = 'success',
                    label = 'In Progress';
                switch (status) {
                    case 'DRAFT':
                        color = 'danger';
                        label = 'Draft';
                        break;
                    case 'COMPLETED':
                        color = 'primary';
                        label = 'Completed';
                        break;
                }
                return '<i class="fas fa-circle text-' + color + '"></i> <span>' + label + '</span>';
            },
            formatDate(value) {
                let date = moment(value);
                return date.format('M/D/YY HH:MM');
            },
            transform(data) {
                // Clean up fields for meta pagination so vue table pagination can understand
                data.meta.last_page = data.meta.total_pages;
                data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
                data.meta.to = data.meta.from + data.meta.count;
                for (let record of data.data) {
                    //Format dates
                    record['created_at'] = this.formatDate(record['created_at']);
                    if (record['completed_at']) {
                        record['duration_at'] = moment(record['created_at']).from(record['completed_at']);
                        record['completed_at'] = this.formatDate(record['completed_at']);
                    } else {
                        record['completed_at'] = '';
                        record['duration_at'] = moment(record['created_at']).fromNow();
                    }
                    //format Status
                    record['status'] = this.formatStatus(record['status']);
                }
                return data;
            },
            fetch() {
                this.loading = true;
                this.additionalParams = this.additionalParams ? this.additionalParams : '&include=assigned';

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
                        this.additionalParams
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
    /deep/ .vuetable-th-slot-ids {
        min-width: 100px;
        white-space: nowrap;
    }
</style>
