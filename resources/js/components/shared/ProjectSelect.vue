<template>
    <div class="form-group" required>
      <label>{{ $t(label) }}</label>
      <multiselect v-model="content"
        :aria-label="$t(label)"
        track-by="id"
        label="title"
        :class="{'border border-danger':error}"
        :loading="!!loading"
        :placeholder="$t('type here to search')"
        :options="options"
        :multiple="true"
        :show-labels="false"
        :searchable="true"
        :internal-search="false"
        @open="load()"
        @search-change="load"
        @select="(selected) => this.lastSelectedId = selected.id"
       >
        <template slot="noResult">
          {{ $t('No elements found. Consider changing the search query.') }}
        </template>
        <template slot="noOptions">
          {{ $t('No Data Available') }}
        </template>
      </multiselect>
      <div class="invalid-feedback d-block" v-for="(error, index) in errors" :key="index">
        <small v-if="error" role="alert" class="text-danger">{{ error }}</small>
      </div>
      <small v-if="error" role="alert" class="text-danger">{{ error }}</small>
      <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    </div>
  </template>
  
  
  <script>
    export default {
      props: ["value", "errors", "label", "helper", "params", "apiGet", "apiList"],
      data() {
        return {
          content: [],
          loading: false,
          options: [],
          error: '',
          defaultProject: null,
          lastSelectedId: null,
        };
      },
      computed: {
  
      },
      watch: {
        content: {
          handler() {
            this.setDefaultProject();
            this.$emit("input", this.content instanceof Array ? this.content.map(item => item.id).join(',') : (this.content ? this.content.id : ''));
          }
        },
        value: {
          handler() {
            this.setUpOptions();
          },
        }
      },
      methods: {
        setUpOptions() {
          if (this.value) {
            const content = [];
            const selected = String(this.value).split(',');
            this.loading = selected.length;
            selected.forEach(project => {
              this.getOptionData(project, content);
            });
          } else {
            this.content.splice(0);
            this.setDefaultProject();
          }
        },
        completeSelectedLoading(content) {
          this.loading = false;
          this.content.splice(0);
          this.content.push(...content);
          this.setDefaultProject();
        },
        getOptionData(id, content) {
          const option = this.options.concat(this.content).find(item => item.id == id);
          if (option) {
            this.loading--;
            content.push(option);
            (!this.loading) ? this.completeSelectedLoading(content) : null;
            return;
          }
          ProcessMaker.apiClient
            .get(this.apiGet)
            .then(response => {
              this.loading--;
              content.push(response.data.data);
              (!this.loading) ? this.completeSelectedLoading(content) : null;
            })
            .catch(error => {
              this.loading--;
              if (error.response.status === 404) {
                this.error = this.$t('Selected not found');
              }
              (!this.loading) ? this.completeSelectedLoading(content) : null;
            });
        },
        load(filter) {
          ProcessMaker.apiClient
            .get(this.apiList + "?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
            .then(response => {
              this.loading = false;
              this.options = response.data.data;
              console.log('THIS.OPTIONS', this.options);
            })
            .catch(err => {
              this.loading = false;
            });
        },
        loadDefaultProject() {
          return ProcessMaker.apiClient
          .get(this.apiList + "?per_page=1&order_by=id&order_direction=DESC")
          .then(response => {
            this.defaultProject = response.data.data[0];
            console.log('DEFAULT PROJECT', this.defaultProject);
            this.$emit("defaultProjectLoaded", this.defaultProject);
          });
        },
        setDefaultProject() {
          if (!this.defaultProject) {
            return;
          }
  
          if (this.content.length === 0) {
            // No projects so give it the default project
            this.content.push(this.defaultProject);
            console.log('THIS.CONTENT', this.content);
            return;
          }
        },
      },
      mounted() {
        this.loadDefaultProject().then(() => {
          this.setUpOptions();
        });
      },
    };
  </script>
  
  <style lang="scss" scoped>
    @import "~@processmaker/vue-multiselect/dist/vue-multiselect.min.css";
  </style>
  