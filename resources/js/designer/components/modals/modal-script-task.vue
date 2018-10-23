<template>
  <b-modal class="scripts-list" ref="modal" size="lg" @hidden="onHidden" title="Script Task" hide-footer>
    <form-input v-model="name" label="Name" helper="Name of the script task"></form-input>
    <form-input :readonly="true" :disabled="true" v-model="assignedScript.title" label="Assigned script" helper="Assigned script"></form-input>
    <div class="form-group">
      <div class="d-flex justify-content-between">
        <input v-model="filter" class="form-control  col-sm-3" placeholder="Search..." @keyup="fetch" >
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
        <pagination single="Script" plural="Scripts" :perPageSelectEnabled="true" @changePerPage="changePerPage"
                    @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
      </div>
    </div>
  </b-modal>
</template>

<script>
  import dataTableMixin from "../../../components/common/mixins/datatable";
  import Pagination from "../../../components/common/Pagination";
  import MonacoEditor from "vue-monaco";
  import actions from '../../actions';
  import EventBus from '../../lib/event-bus';
  import {FormInput} from '@processmaker/vue-form-elements/src/components';

  export default {
      components: {
          MonacoEditor,
          Pagination,
          FormInput
      },
      mixins: [dataTableMixin],
      props: {
          'processId': String,
          'selectedElement': Object
      },
      data() {
          return {
              items: [],
              filter: '',
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
                      title: "Description",
                      name: "description",
                      sortField: "description"
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
              data: [],
              name: this.selectedElement.attributes.name,
              configuration: this.selectedElement.attributes.configuration,
              assignedScriptName: '',
              assignedScript: {
                  title: '...'
              }
          };
      },
      watch: {
          name(name) {
              this.updateElement({
                  name: name,
              });
          },
          assignedScript(data) {
              this.updateElement({
                  script: data.code,
                  scriptFormat: this.getMimeType(data.language),
                  scriptRef: data.id,
                  scriptConfiguration: this.configuration,
              });
          }
      },
      methods: {
          getMimeType(language) {
              let mimeTypes = {
                  'php': "application/x-php",
                  'javascript': "application/javascript",
                  'lua': "application/x-lua"
              };
              return mimeTypes[language] === undefined ? "application/x-" + language
                      : mimeTypes[language];
          },
          onHidden() {
              this.$emit('hidden');
          },
          onCancel() {
              this.$refs.modal.hide();
          },
          onAssign(data, index) {
              this.assignedScript = data;
          },
          /**
           * Update the script task element.
           *
           * @param Object data
           */
          updateElement(data) {
              data.id = this.selectedElement.id;
              let action = actions.bpmn.task.update(data);
              EventBus.$emit(action.type, action.payload);
          },
          fetch() {
              this.loading = true;
              const CancelToken = ProcessMaker.apiClient.CancelToken;
              //Get assigned script
              let scriptRef = this.selectedElement.attributes['pm:scriptRef'];
              if (scriptRef) {
                  ProcessMaker.apiClient
                          .get('process/' + this.processId + '/script/' + scriptRef)
                          .then(response => {
                              this.assignedScript = response.data;
                              console.log(response.data);
                          });
              } else {
                  this.assignedScript = {
                      title: '(unassigned)'
                  };
              }
              //Get the list of scripts
              ProcessMaker.apiClient
                      .get('process/' +
                              this.processId +
                              '/scripts?page=' +
                              this.page +
                              '&per_page=' +
                              this.perPage +
                              '&filter=' +
                              this.filter +
                              '&order_by=' +
                              this.orderBy +
                              '&order_direction=' +
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
