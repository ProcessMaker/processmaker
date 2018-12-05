<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">

            <template slot="taskName" slot-scope="props">
                <!--<b-link @click="onAction('openTask', props.rowData, props.rowIndex)">-->
                    <!--{{props.rowData.name}}-->
                <!--</b-link>-->

                <a v-bind:href="props.rowData.url">{{props.rowData.name}}</a>
            </template>

            <template slot="changeStatus" slot-scope="props">
                <span v-if="props.rowData.read_at === null"  class="badge badge-pill badge-info"
                      style="cursor:pointer" @click="dismiss(props.rowData.id)">
                    Dismiss
                </span>

                <span v-if="props.rowData.read_at !==  null"  class="badge badge-pill badge-secondary"
                      style="cursor:pointer" @click="unread(props.rowData.id)">
                    Unread
                </span>
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
                orderBy: "created_at",

                sortOrder: [
                    {
                        field: "created_at",
                        sortField: "created_at",
                        direction: "asc"
                    },
                ],
                fields: [
                    {
                        title: "TASK",
                        name: "__slot:taskName",
                    },
                    {
                        title: "PROCESS",
                        name: "processName"
                    },
                    {
                        title: "id",
                        name: "id"
                    },
                    {
                        title: "REQUEST",
                        name: "__slot:changeStatus",
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
            dismiss(id) {
                ProcessMaker.removeNotifications([id]);
                document.location.reload();
            },

            unread(id){
                ProcessMaker.unreadNotifications([id]);
                document.location.reload();
            },

            onAction(action, rowData, index) {
                if (action === "openTask") {
                    let link = rowData.url;
                    window.location = link;
                }
            },
            formatDueDate(value) {
                let dueDate = moment(value);
                let now = moment();
                let diff = dueDate.diff(now, "hours");
                let color =
                    diff < 0 ? "text-danger" : diff <= 1 ? "text-warning" : "text-primary";
                return '<span class="' + color + '">' + this.formatDate(dueDate) +
                    "</span>";
            },
            getTaskStatus() {
                let path = new URL(location.href);
                let status = path.searchParams.get('status');
                return ((status === null) ? 'ACTIVE' : status);
            },

            getSortParam: function () {
                if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
                    return "&order_by=" + this.sortOrder[0].sortField +
                        "&order_direction=" + this.sortOrder[0].direction;
                } else {
                    return '';
                }
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
                        "notifications?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        this.getSortParam()
                        , {
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

    /deep/ tr td:nth-child(4) {
        padding: 6px 10px;
    }
</style>

