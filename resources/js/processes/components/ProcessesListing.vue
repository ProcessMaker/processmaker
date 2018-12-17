<template>
    <div class="data-table">
        <div class="card card-body table-card">
            <vuetable
                    :dataManager="dataManager"
                    :sortOrder="sortOrder"
                    :css="css"
                    :api-mode="false"
                    @vuetable:pagination-data="onPaginationData"
                    :fields="fields"
                    :data="data"
                    data-path="data"
                    pagination-path="meta"
            >
                <template slot="name" slot-scope="props">{{props.rowData.name}}</template>

                <template slot="owner" slot-scope="props">
                    <avatar-image
                            class="d-inline-flex pull-left align-items-center"
                            size="25"
                            :input-data="props.rowData.user"
                            hide-name="true"
                    ></avatar-image>
                </template>

                <template slot="actions" slot-scope="props">
                    <div class="actions">
                        <div class="popout">
                            <b-btn
                                    variant="link"
                                    @click="onAction('edit-designer', props.rowData, props.rowIndex)"
                                    v-b-tooltip.hover
                                    title="Open Modeler"
                            >
                                <i class="fas fa-pen-square fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                    variant="link"
                                    @click="onAction('edit-item', props.rowData, props.rowIndex)"
                                    v-b-tooltip.hover
                                    title="Config"
                            >
                                <i class="fas fa-cog fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                    variant="link"
                                    @click="onAction('remove-item', props.rowData, props.rowIndex)"
                                    v-b-tooltip.hover
                                    title="Remove"
                                    v-if="props.rowData.status === 'ACTIVE'"
                            >
                                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                    variant="link"
                                    @click="onAction('activate-item', props.rowData, props.rowIndex)"
                                    v-b-tooltip.hover
                                    title="Activate"
                                    v-if="props.rowData.status === 'INACTIVE'"
                            >
                                <i class="fas fa-lightbulb fa-lg fa-fw"></i>
                            </b-btn>
                        </div>
                    </div>
                </template>
            </vuetable>

            <pagination
                    single="Process"
                    plural="Processes"
                    :perPageSelectEnabled="true"
                    @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange"
                    ref="pagination"
            ></pagination>
        </div>
    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter", "id", "status"],
        data() {
            return {
                orderBy: "name",
                sortOrder: [
                    {
                        field: "name",
                        sortField: "name",
                        direction: "asc"
                    }
                ],

                fields: [
                    {
                        title: "Name",
                        name: "__slot:name",
                        field: "name",
                        sortField: "name"
                    },
                    {
                        title: "Category",
                        name: "category.name",
                        sortField: "category.name"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status",
                        callback: this.formatStatus
                    },
                    {
                        title: "Owner",
                        name: "__slot:owner",
                        callback: this.formatUserName
                    },
                    {
                        title: "Modified",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: "formatDate"
                    },
                    {
                        title: "Created",
                        name: "created_at",
                        sortField: "created_at",
                        callback: "formatDate"
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ]
            };
        },

        methods: {
            goToEdit(data) {
                window.location = "/processes/" + data + "/edit";
            },
            goToDesigner(data) {
                window.location = "/modeler/" + data;
            },
            onAction(action, data, index) {
                switch (action) {
                    case "edit-designer":
                        this.goToDesigner(data.id);
                        break;

                    case "edit-item":
                        this.goToEdit(data.id);
                        break;
                    case "activate-item":
//                        ProcessMaker.apiClient
//                            .post("processes/" + data.id)
//                            .then(response => {
//                                ProcessMaker.alert("User Marked As Deleted", "warning");
//                                this.$emit("reload");
//                            });
//                        break;
                    case "remove-item":
                        ProcessMaker.confirmModal(
                            "Caution!",
                            "<b>Are you sure to inactive the process </b>'" + data.name + "'?",
                            "",
                            () => {
                                ProcessMaker.apiClient
                                    .delete("processes/" + data.id)
                                    .then(response => {
                                        ProcessMaker.alert("User Marked As Deleted", "warning");
                                        this.$emit("reload");
                                    });
                            }
                        );
                        break;
                }
            },
            formatStatus(status) {
                status = status.toLowerCase();
                let bubbleColor = {
                    active: "text-success",
                    inactive: "text-danger",
                    draft: "text-warning",
                    archived: "text-info"
                };
                let response =
                    '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
                status = status.charAt(0).toUpperCase() + status.slice(1);
                return '<div style="white-space:nowrap">' + response + status + "</div>";
            },
            formatUserName(user) {
                return (
                    (user.avatar
                        ? this.createImg({
                            src: user.avatar,
                            class: "rounded-user"
                        })
                        : '<i class="fa fa-user rounded-user"></i>') +
                    "<span>" +
                    user.fullname +
                    "</span>"
                );
            },
            createImg(properties) {
                let container = document.createElement("div");
                let node = document.createElement("img");
                for (let property in properties) {
                    node.setAttribute(property, properties[property]);
                }
                container.appendChild(node);
                return container.innerHTML;
            },
            fetch() {
                this.loading = true;
                //change method sort by user
                this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
                //change method sort by slot name
                this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

                let url = (this.status === null || this.status === '' || this.status === undefined)
                    ? 'processes?'
                    : 'processes?status=' + this.status + '&';
                console.log(url);

                // Load from our api client
                ProcessMaker.apiClient
                    .get( url +
                        "page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        this.orderBy +
                        "&order_direction=" +
                        this.orderDirection +
                        "&include=category,user"
                    )
                    .then(response => {
                        this.data = this.transform(response.data);
                        this.loading = false;
                    });
            }
        },

        computed: {}
    };
</script>

<style lang="scss" scoped>
    /deep/ th#_updated_at {
        width: 14%;
    }

    /deep/ th#_created_at {
        width: 14%;
    }
</style>