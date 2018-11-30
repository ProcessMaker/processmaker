<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
                <div class="actions">
                    <div class="popout">
                        <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                        <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="User" plural="Users" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>


<script>
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter"],
        data() {
            return {
                orderBy: "username",
                // Our listing of users
                sortOrder: [{
                    field: "username",
                    sortField: "username",
                    direction: "asc"
                }],
                fields: [{
                    title: "Username",
                    name: "username",
                    sortField: "username"
                },
                    {
                        title: "Full Name",
                        name: "fullname",
                        sortField: "fullname"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status",
                        callback: this.formatStatus
                    },
                    {
                        title: "Email",
                        name: "email",
                        sortField: "email"
                    },
                    {
                        title: "Login",
                        name: "loggedin_at",
                        sortField: "loggedin_at",
                        callback: 'formatDate'
                    },
                    {
                        title: "Created At",
                        name: "created_at",
                        sortField: "created_at",
                        callback: 'formatDate'
                    },
                    {
                        title: "Updated At",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: 'formatDate'
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },
        methods: {
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {
                    active: "text-success",
                    inactive: "text-danger",
                    draft: "text-warning",
                    archived: "text-info"
                };
                return (
                    '<i class="fas fa-circle ' +
                    bubbleColor[status] +
                    ' small"></i> ' +
                    status.charAt(0).toUpperCase() +
                    status.slice(1)
                );
            },
            goToEdit(data) {
                window.location = "/admin/users/" + data + "/edit";
            },
            onAction(action, data, index) {
                switch (action) {
                    case "edit-item":
                        this.goToEdit(data.id);
                        break;
                    case "remove-item":
                        ProcessMaker.confirmModal(
                            "Caution!",
                            "<b>Are you sure to inactive the user </b>" + data.fullname + "?",
                            "",
                            () => {
                                ProcessMaker.apiClient
                                    .delete("users/" + data.id)
                                    .then(response => {
                                        ProcessMaker.alert("User Marked As Deleted", "warning");
                                        this.$emit("reload");
                                    });
                            }
                        );
                        break;
                }
            },
            fetch() {
                this.loading = true;
                //change method sort by user
                this.orderBy = this.orderBy === "fullname" ? "firstname" : this.orderBy;
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "users?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
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

<style lang="scss" scoped>
</style>
