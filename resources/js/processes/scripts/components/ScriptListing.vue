<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">

            <template slot="title" slot-scope="props">
                <b-link @click="onAction('edit', props.rowData, props.rowIndex)">
                    {{props.rowData.title}}
                </b-link>
            </template>

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
        <pagination single="Script" plural="Scripts" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
</template>

<script>
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ["filter", "id"],
        data() {
            return {
                orderBy: "title",

                sortOrder: [
                    {
                        field: "title",
                        sortField: "title",
                        direction: "asc"
                    }
                ],

                fields: [
                    {
                        title: "Title",
                        name: "title",
                        sortField: "title"
                    },
                    {
                        title: "Language",
                        name: "language",
                        sortField: "language",
                        callback: this.formatLanguage
                    },
                    {
                        title: "Modified",
                        name: "updated_at",
                        sortField: "updated_at",
                        callback: 'formatDate'
                    },
                    {
                        title: "Created",
                        name: "created_at",
                        sortField: "created_at",
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
            goToEdit(data) {
                window.location = "/processes/scripts/" + data + "/edit";
            },
            onAction(action, data, index) {
                switch (action) {
                    case "edit-item":
                        this.goToEdit(data.id);
                        break;
                    case "remove-item":
                        ProcessMaker.confirmModal(
                            "Caution!",
                            "<b>Are you sure to delete the Script </b>" + data.title + "?",
                            "",
                            () => {
                                this.$emit("delete", data);
                            }
                        );
                        break;
                        break;
                }
            },
            formatLanguage(language) {
                return language.toUpperCase();
            },
            fetch() {
                this.loading = true;
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "scripts" +
                        "?page=" +
                        this.page +
                        "&per_page=" +
                        this.perPage +
                        "&filter=" +
                        this.filter +
                        "&order_by=" +
                        this.orderBy +
                        "&order_direction=" +
                        this.orderDirection +
                        "&include=user"
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
    /deep/ th#_total_users {
        width: 150px;
        text-align: center;
    }

    /deep/ th#_description {
        width: 250px;
    }
</style>
