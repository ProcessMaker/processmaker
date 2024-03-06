<template>
    <div class="column-chooser" ref="columnChooser">
        <column-config @create="onConfigCreated" @update="onConfigUpdated"></column-config>
        <div>
            <div ref="columnBefore">
                <div class="pb-4">
                    <small class="form-text text-muted">{{ $t('Drag any columns you want to view in your table from right to left. You may also sort, configure, and remove columns.') }}</small>
                </div>
                <div class="title-container  d-flex flex-row align-content-stretch">
                    <div class="w-50 mr-3">
                        <h5>{{ $t('Active Columns') }}</h5>
                    </div>
                    <div class="w-50">
                        <h5>{{ $t('Available Columns') }}</h5>
                    </div>                                    
                </div>
            </div>
            <div class="column-container d-flex flex-row align-content-stretch" ref="columnContainer">
                <div class="w-50 mr-3">
                    <draggable group="columns" class="border bg-muted px-3 draggable-list draggable-current" :list="currentColumns">
                        <column v-for="(element, index) in currentColumns" 
                                :column="element"
                                :key="element.field"
                                @config="configColumn(index)"
                                @remove="removeColumn(index)"></column>
                    </draggable>
                </div>
                <div class="w-50">
                    <div v-if="availableColumnsDirect === false" class="d-flex align-items-center justify-content-center border bg-muted h-100 w-100 text-center px-3 draggable-list draggable-available">
                        <data-loading-basic
                        desc="Finding available columns..."
                        :is-loaded="false"
                        ></data-loading-basic>
                    </div>
                    <draggable group="columns" :list="availableColumnsDirect" v-else-if="availableColumnsDirect === null || availableColumnsDirect === undefined || (availableColumnsDirect && availableColumnsDirect.length === 0)" class="d-flex flex-column align-items-center justify-content-center border bg-muted px-3 draggable-list draggable-available">
                        <data-loading-basic
                        :empty="$t('No Available Columns')"
                        empty-icon="table"
                        :is-loaded="true"
                        :is-empty="true"
                        desc="Finding available columns..."
                        class="w-100"
                        ></data-loading-basic>
                    </draggable>
                    <draggable v-else class="border bg-muted px-3 draggable-list draggable-available" group="columns" :list="availableColumnsDirect">
                        <column v-for="element in availableColumnsDirect"
                                :column="element"
                                :key="element.field"
                                :without-config="true"
                                :without-remove="true"
                                ></column>
                    </draggable>                        
                </div>
            </div>
            <div ref="columnAfter">
                <div class="footer-container d-flex flex-row align-content-stretch">
                    <div class="w-50 mr-3">
                        
                    </div>
                    <div class="w-50">
                        <a @click="onAddCustomColumn" class="column-card column-add card card-body bg-primary text-white p-2 my-3">
                            <div>
                                <span class="pl-1 pr-2">
                                    <i class="fa fa-fw fa-plus"></i>
                                </span>
                                {{ $t('Add Custom Column') }}
                            </div>
                        </a>
                    </div>                                    
                </div>
                <div class="d-flex">
                    <div class="mr-auto">
                        <b-button variant="outline-danger" @click="onReset">
                            <i class="fa fa-undo"></i> {{ $t('Reset to Default') }}
                        </b-button>
                    </div>
                    <div class="d-flex justify-content-end">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Column from "./Column";
import ColumnConfig from "./ColumnConfig";
import DataLoadingBasic from "./DataLoadingBasic";
import draggable from "vuedraggable";

export default {
    components: {
        Column,
        ColumnConfig,
        DataLoadingBasic,
        draggable
    },
    props: {
        value: {
            type: Array,
            default: function () {
                return [];
            },
        },
        defaultColumns: {
            default: null,
        },
        availableColumns: {
            default: false,
        },
        dataColumns: {
            default: null,
        },
    },
    data() {
        return {
            userFilter: "",
            groupFilter: "",
            columnToConfig: {
                index: 0,
                data: {}
            },
            configShown: false,
            usersAndGroups: null,
            disabled: true,
            firstRun: true,
            selectedUserOrGroup: null,
            custom_icon: false,
            dataGroups: [],
            errors: {
                name: null,
                description: null,
                category: null,
                status: null,
                screen: null
            },
            screens: null,
            currentColumns: [],
            availableColumnsDirect: [],
        }
    },
    mounted() {
      //Set current columns based on value
      this.currentColumns = this.value;
        
      //Resize columns on mount
      this.resizeColumns();
      
      //Resize columns on window resize
      window.onresize = this.resizeColumns;
      
      // Must use Bootstrap's jQuery to setup tab change listener
      $('a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
        this.resizeColumns();
      })
    },
    watch: {
        value: {
            handler: function(value) {
                this.currentColumns = value;
                if (! this.firstRun) {
                  this.$emit('input', this.currentColumns);
                } else {
                  this.firstRun = false;
                }
            },
            deep: true
        },
        availableColumns: {
            handler: function(value) {
                this.availableColumnsDirect = value;
            },
            deep: true
        }
    },
    methods: {
        retrieveColumns() {
            ProcessMaker.apiClient.get('saved-searches/' + this.formData.id + '/columns?include=default')
                .then((response) => {
                    if (response.data && response.data.default) {
                        this.defaultColumns = response.data.default;
                    }
                });

            ProcessMaker.apiClient.get('saved-searches/' + this.formData.id + '/columns?include=available,data', {timeout: 120000})
                .then((response) => {
                    if (response.data) {
                        if (response.data.available) {
                            this.availableColumnsDirect = response.data.available;
                        }

                        if (response.data.data) {
                            this.dataColumns = response.data.data;
                        }
                    }
                })
                .catch(error => {
                    this.availableColumnsDirect = [];
                });
        },
        resizeColumns() {
          if(!document.querySelector('.tab-content')) {
            return;
          }
          this.resizeColumnTabPane();
          this.resizeColumnContainer();
        },
        resizeColumnTabPane() {
          let top = document.querySelector('.tab-content').getBoundingClientRect().top;
          let windowHeight = window.innerHeight;
          
          let height = (windowHeight - top);
          document.querySelector('.tab-content-columns').style.height = `${height}px`;
        },
        resizeColumnContainer() {
          let containerHeight = document.querySelector('.tab-content').offsetHeight;
          let beforeHeight = this.$refs.columnBefore.offsetHeight;
          let afterHeight = this.$refs.columnAfter.offsetHeight;
          
          let height = (containerHeight - (beforeHeight + afterHeight));
          this.$refs.columnContainer.style.height = `${height}px`;
          this.$refs.columnContainer.style.maxHeight = `${height}px`;
        },
        onFileError(message) {
          this.errors = { file: [message] };
        },
        clearFileError() {
          if (this.errors && this.errors.file) {
            delete this.errors.file;
          }
        },
        configColumn(index) {
            this.columnToConfig = {
                index: index,
                data: this.currentColumns[index],
                new: false
            };
            this.configShown = true;
        },
        onConfigCreated(item) {
            this.currentColumns.push({
                label: item.label,
                field: item.field,
                sortable: item.sortable,
                format: item.format,
                mask: item.mask,
                default: false
            });
            this.configShown = false;
        },
        onConfigUpdated(index, item) {
            this.currentColumns[index].label = item.label;
            this.currentColumns[index].field = item.field;
            this.currentColumns[index].sortable = item.sortable;
            this.currentColumns[index].format = item.format;
            this.currentColumns[index].mask = item.mask;
            this.configShown = false;
        },
        removeColumn(index) {
            if (this.availableColumnsDirect !== false) {
                this.availableColumnsDirect.unshift(this.currentColumns[index]);
            }
            this.currentColumns.splice(index, 1);
        },
        onAddCustomColumn() {
            this.columnToConfig = {
                data: {
                    label: '',
                    field: '',
                    sortable: false,
                    format: '',
                    mask: '',
                },
                new: true
            };
            this.configShown = true;
        },
        cloneObject(source) {
            return Object.assign({}, source);
        },
        cloneArray(source) {
            if (source && source.length) {
                return source.slice(0);
            }
        },
        onReset() {
            ProcessMaker.confirmModal(
                this.$t("Reset to Default"),
                this.$t("Are you sure you want to reset your columns to the default layout?"),
                "",
                () => {

                    if (this.defaultColumns !== null) {
                        this.currentColumns = this.cloneArray(this.defaultColumns);
                    } else {
                        this.currentColumns = [];
                    }

                    if (this.dataColumns !== null) {
                        this.availableColumnsDirect = this.cloneArray(this.dataColumns);
                    } else {
                        this.availableColumnsDirect = [];
                    }

                    this.$emit('input', this.currentColumns);
                }
            );
        },
        onCancel() {
            window.location.href = ProcessMaker.cancelRoute;
        },
        onSave() {
            let formData = this.formData;
            ProcessMaker.apiClient.put('saved-searches/' + this.formData.id, formData)
                .then((response) => {
                    ProcessMaker.alert(this.$t('The search was saved.'), 'success')
                })
                .catch(error => {
                    this.errors = error.response.data.errors;
                });
        },
        onUpdateUsers(value) {
            this.formData.users = value;
        },
        onUpdateGroups(value) {
            this.formData.groups = value;
        },
        onSaveUsers() {
            this.onSave();
        },
        onSaveGroups() {
            let formData = {};
            formData.group_permissions = this.$refs.groupPermissions.permissions;
            ProcessMaker.apiClient.put('collections/' + this.formData.id, formData)
                .then((response) => {
                    ProcessMaker.alert(this.$t('The group permissions were saved.'), 'success')
                })
                .catch(error => {
                    this.errors = error.response.data.errors;
                });
        }
    }
};
</script>

<style lang="scss">
    .column-container {
        min-height: 100px;
    }
</style>
