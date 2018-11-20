<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
        </vuetable>
        <pagination single="Package" plural="Packages" :perPageSelectEnabled="true" @changePerPage="changePerPage"
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
                orderBy: "name",
                // Our listing of packages
                sortOrder: [{
                    field: "name",
                    sortField: "name",
                    direction: "asc"
                }],
                fields: [
                    {
                        title: "Name",
                        name: "friendly_name",
                        sortField: "friendly_name"
                    },
                    {
                        title: "Composer Name",
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Description",
                        name: "description",
                        sortField: "description"
                    },
                    {
                        title: "Version",
                        name: "version",
                        sortField: "version",
                    },
                    {
                        title: "Expire in",
                        name: "expire_in",
                        sortField: "expire_in",
                        callback: 'formatDate'
                    }
                ]
            };
        },
        methods: {
            fetch() {
                this.loading = true;
                //change method sort by package
                this.orderBy = this.orderBy === "name" ? "version" : this.orderBy;
                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "packages?page=" +
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
