<template>
    <div class="data-table">
        <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                  pagination-path="meta">
            <template slot="actions" slot-scope="props">
            <div class="actions">
                <i class="fas fa-ellipsis-h"></i>
                <div class="popout">
                    <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                    <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                </div>
          </div>
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
                    },
                    {
                        name: "__slot:actions",
                        title: ""
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
                        this.data = response.data;
                        this.loading = false;
                    });
            },
            onPaginationData() { },
            onAction(action, data, index) {
                switch (action) {
                    case "edit-item":
                        this.$emit('edit', data)
                    case "remove-item":
                        this.$emit('delete', data)
                }
            }
        }
    }
</script>