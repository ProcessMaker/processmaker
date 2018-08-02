<template>
    <b-modal class="output-docs" ref="modal" size="lg" @hidden="onHidden" title="Output Documents" hide-footer>
        <div class="form-group">
            <div class="d-flex justify-content-between">
                <filter-bar></filter-bar>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-plus fa-md"></i> Create</button>
            </div>
            <div class="data-table">
                <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                          :fields="fields" :data="data" data-path="data" pagination-path="meta">
                    <template slot="actions" slot-scope="props">
                        <div class="actions">
                            <i class="fas fa-ellipsis-h"></i>
                            <div class="popout">
                                <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)"
                                       v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                                <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)"
                                       v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                            </div>
                        </div>
                    </template>
                </vuetable>
            </div>
        </div>
    </b-modal>
</template>

<script>
    import FilterBar from "../../../components/FilterBar";

    Vue.component('filter-bar', FilterBar);
    import datatableMixin from "../../../components/common/mixins/datatable";

    export default {
        mixins: [datatableMixin],
        props: ['filter', 'processUid'],
        data() {
            return {
                // form models here
                'messageFieldName': "Name",
                items: [
                    {message: 'Foo'},
                    {message: 'Bar'}
                ],
                orderBy: "code",

                sortOrder: [
                    {
                        field: "ID",
                        sortField: "id",
                        direction: "asc"
                    }
                ],
                fields: [
                    {
                        title: "Date Uploaded",
                        name: "created_at",
                        sortField: "created_at"
                    },
                    {
                        title: "Title",
                        name: "title",
                        sortField: "title"
                    },
                    {
                        title: "Type",
                        name: "type",
                        sortField: "type"
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ],
                data: []
            };
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onCancel() {
                this.$refs.modal.hide()
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
                    .get('process/' + this.processUid + '/output-documents',
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
                    });
            }
        },
        mounted() {
            // Show our modal as soon as we're created
            this.$refs.modal.show();
        },

    };


</script>
