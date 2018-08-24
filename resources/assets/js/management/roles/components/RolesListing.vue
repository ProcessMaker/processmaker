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
                        <b-btn variant="action" @click="onDelete(props.rowData, props.rowIndex)" v-b-tooltip.hover
                               title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                        <b-btn variant="action" @click="onAction('users-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Users"><i class="fas fa-users"></i></b-btn>
                        <b-btn variant="action" @click="onAction('permissions-item', props.rowData, props.rowIndex)"
                               v-b-tooltip.hover title="Permissions"><i class="fas fa-user-lock"></i></b-btn>
                    </div>
                </div>
            </template>
        </vuetable>
        <pagination single="Role" plural="Roles" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
        <b-modal ref="editItem" size="md" centered title="Create New Role">
            <form>
                <div class="form-group">
                    <label for="add-role-code">Code</label>
                    <input id="add-role-code" class="form-control" v-model="code">
                </div>
                <div class="form-group">
                    <label for="add-role-name">Name</label>
                    <input id="add-role-name" class="form-control" v-model="name">
                </div>
                <div class="form-group">
                    <label for="add-role-name">Description</label>
                    <input id="add-role-name" class="form-control" v-model="description">
                </div>
                <div class="form-group">
                    <label for="add-role-status">Status</label>
                    <select class="form-control" id="add-role-status" v-model="status">
                        <option value="ACTIVE">Active</option>
                        <option value="INACTIVE">Inactive</option>
                    </select>
                </div>
            </form>
            <template slot="modal-footer">
                <b-button @click="hideEditModal" class="btn btn-outline-success btn-sm text-uppercase">
                    Cancel
                </b-button>
                <b-button @click="submitEdit" class="btn btn-success btn-sm text-uppercase">
                    Save
                </b-button>
            </template>
        </b-modal>
    </div>
</template>

<script>
    import Vuetable from "vuetable-2/src/components/Vuetable";
    import Pagination from "../../../components/common/Pagination";
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter"],
        data() {
            return {
                orderBy: "code",
                editData: null,
                sortOrder: [
                    {
                        field: "code",
                        sortField: "code",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        title: "Code",
                        name: "code",
                        sortField: "code"
                    },
                    {
                        title: "Name",
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Description",
                        name: "description",
                        sortField: "description"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status",
                        callback: this.formatStatus
                    },
                    {
                        title: "Active Users",
                        name: "total_users",
                        sortField: "total_users",
                        callback: this.formatActiveUsers
                    },
                    {
                        title: "Created At",
                        name: "created_at",
                        sortField: "created_at",
                        callback: this.formatDate
                    },
                    {
                        title: "Updated At",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: this.formatDate
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ],
                name: '',
                code: '',
                description: '',
                uid: '',
                status: '',
                curIndex: '',
            };
        },
        methods: {
            onAction(action, data, index) {
                switch (action) {
                    case 'edit-item' :
                        this.showEditModal(data, index)
                }
            },
            onDelete(data, index) {
                let that = this;
                ProcessMaker.confirmModal('Caution!', '<b>Are you sure to delete the role </b>' + data.name + '?', '', function () {
                    ProcessMaker.apiClient
                        .delete('roles/' + data.uid)
                        .then(response => {
                            ProcessMaker.alert('Role successfully eliminated', 'success');
                            that.fetch();
                        });
                });
            },
            showEditModal(data, index) {
                this.name = this.data.data[index].name;
                this.code = this.data.data[index].code;
                this.status = this.data.data[index].status;
                this.description = this.data.data[index].description;
                this.uid = this.data.data[index].uid;
                this.curIndex = index;
                this.$refs.editItem.show();
            },
            hideEditModal() {
                this.$refs.editItem.hide()
            },
            submitEdit(rowIndex) {
                window.ProcessMaker.apiClient.put('roles/' + this.uid, {
                    'uid': this.uid,
                    'name': this.name,
                    'code': this.code,
                    'description': this.description,
                    'status': this.status
                })
                    .then((response) => {
                        ProcessMaker.alert("Saved", "success")
                        this.clearForm()
                        this.hideEditModal()
                        this.fetch()
                    })
                    .catch((err) => {
                        ProcessMaker.alert("There was an error with your edit", "danger")
                    })
            },
            clearForm(curIndex) {
                this.name = '',
                    this.code = '',
                    this.description = '',
                    this.uid = '',
                    this.status = ''
            },
            formatActiveUsers(value) {
                return '<div class="text-center">' + value + "</div>";
            },
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {'active': 'text-success', 'inactive': 'text-danger', 'draft': 'text-warning', 'archived': 'text-info'};
                let response = '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
                status = status.charAt(0).toUpperCase() + status.slice(1);
                return response + status;
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
                        "roles?page=" +
                        this.page +
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
                    })
                    .catch(error => {
                        // Undefined behavior currently, show modal?
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
        width: 225px;
    }

</style>
