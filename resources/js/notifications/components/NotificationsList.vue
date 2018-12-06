<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">

            <template slot="subject" slot-scope="props">
                <a v-bind:href="props.rowData.url">{{props.rowData.name}}</a>
            </template>

            <template slot="changeStatus" slot-scope="props">
                <span v-if="props.rowData.read_at === null" style="cursor:pointer" @click="read(props.rowData.id)">
                   <i class="far fa-envelope"></i>
                    Unread
                </span>

                <span v-if="props.rowData.read_at !==  null" style="cursor:pointer" @click="unread(props.rowData.id)">
                   <i class="far fa-envelope-open"></i>
                    Read
                </span>
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
                orderBy: "",

                sortOrder: [
                ],
                fields: [
                    {
                        title: "STATUS",
                        name: "__slot:changeStatus",
                        sortField: "read_at"
                    },
                    {
                        title: "SUBJECT",
                        name: "__slot:subject",
                        sortField: "name",
                    },
                    {
                        title: "DATE CREATED",
                        name: "created_at",
                        sortField: "created_at"
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
            read(id) {
                ProcessMaker.removeNotifications([id]);
                this.fetch();
            },

            unread(id){
                ProcessMaker.unreadNotifications([id]);
                this.fetch();
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

