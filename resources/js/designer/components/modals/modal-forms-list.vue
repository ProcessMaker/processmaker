<template>
    <b-modal class="form-docs" ref="modal" size="lg" @hidden="onHidden" hide-footer title="Task Form">
      <div class="ibox-content m-b-sm border-bottom">
        <div class="p-xs">
          <h2>{{formTitle}}</h2>
          <div v-show="formId">
            ID: <span class="badge badge-pill badge-secondary">{{formId}}</span>
          </div>
        </div>
      </div>
        <div class="form-group">
            <div class="d-flex justify-content-between">
                <filter-bar></filter-bar>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-plus fa-md"></i> Create</button>
            </div>
            <div class="data-table">
                <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"
                          @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data"
                          pagination-path="meta">
                    <template slot="actions" slot-scope="props">
                        <div class="actions">
                            <i class="fas fa-ellipsis-h"></i>
                            <div class="popout">
                                <b-btn variant="action" @click="onAssign(props.rowData, props.rowIndex)"
                                       v-b-tooltip.hover title="Assign"><i class="fas fa-check-circle"></i></b-btn>
                            </div>
                        </div>
                    </template>
                </vuetable>
                <pagination single="Form" plural="Forms" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                            @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
            </div>
        </div>
    </b-modal>
</template>

<script>
    import FilterBar from "../../../components/FilterBar";
    import actions from "../../actions"
    import EventBus from "../../lib/event-bus"

    Vue.component('filter-bar', FilterBar);
    import Vuetable from "vuetable-2/src/components/Vuetable";
    import datatableMixin from "../../../components/common/mixins/datatable";
    import Pagination from "../../../components/common/Pagination";

    export default {
        components: {Pagination},
        mixins: [datatableMixin],
        props: ['processId', 'filter','selectedElement'],
        data() {
            return {
                // form models here
                'messageFieldName': "Name",
                items: [],
                orderBy: "title",

                sortOrder: [
                    {
                        field: "ID",
                        sortField: "id",
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
                        title: "Description",
                        name: "description",
                        sortField: "description"
                    },
                    {
                        name: "__slot:actions",
                        title: ""
                    }
                ],
                data: [],
                formId: this.selectedElement.attributes['pm:formRef'],
                formTitle: '...'
            };
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onCancel() {
                this.$refs.modal.hide()
            },
            onAssign(data, index) {
                //Set property to task
                let formRef = data.id;
                //Hide popup
                this.$refs.modal.hide()
                let action = actions.bpmn.shape.assignTask({formRef})
                EventBus.$emit(action.type, action.payload)
            },
            fetch() {
                this.loading = true;
                if (this.cancelToken) {
                    this.cancelToken();
                    this.cancelToken = null;
                }
                const CancelToken = ProcessMaker.apiClient.CancelToken;
                //Load Form Assigned label
                let formRef = this.selectedElement.attributes['pm:formRef'];
                if (formRef) {
                  ProcessMaker.apiClient
                    .get('process/' + this.processId + '/form/' + formRef)
                    .then(response => {
                        this.formTitle = response.data.title;
                    });
                } else {
                  this.formTitle = '(Unassigned)';
                }
                //Load Forms list
                ProcessMaker.apiClient
                    .get('process/' + this.processId + '/forms',
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
            this.$refs.modal.show();
        }
    };


</script>
