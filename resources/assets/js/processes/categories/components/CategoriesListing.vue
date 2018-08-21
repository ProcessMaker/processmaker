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
                        name: "name",
                        sortField: "name"
                    },
                    {
                        title: "Status",
                        name: "status",
                        sortField: "status"
                    }
                ]
            }
        },
        methods: {
            fetch() { },
            onPaginationData() { },
        }
    }
</script>