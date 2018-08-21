<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
            </template>
        </vuetable>
    </div>
</template>

<script>
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
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
                        title: "Category",
                        name: "cat_name",
                        sortField: "name"
                    },
                    {
                        title: "Status",
                        name: "cat_uid",
                        sortField: "status"
                    }
                ]
            }
        },
        methods: {
            fetch() {
                this.loading = true;

                // Load from our api client
                ProcessMaker.apiClient
                    .get(
                        "categories",
                    )
                    .then(response => {
                        // this.data = this.transform(response.data);
                        this.data = response.data;
                        this.loading = false;
                    });
            },
            onPaginationData() { },
        }
    }
</script>