<template>
  <div class="form-group">
    <label>{{ $t(label) }}</label>
    <multiselect v-model="content" :aria-label="$t(label)" track-by="id" label="title"
      :class="{ 'border border-danger': error }" :loading="!!loading" :placeholder="$t('type here to search')"
      :options="options" :multiple="true" :show-labels="false" :searchable="true" :internal-search="true" @open="load()"
      @search-change="load" @select="(selected) => this.lastSelectedId = selected.id">
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
  props: ["value", "errors", "label", "helper", "params", "apiGet", "apiList", "projectId"],
  data() {
    return {
      content: [],
      loading: false,
      options: [],
      error: '',
      lastSelectedId: null,
      currentProject: null,
      initialLoadExecuted: false,
    };
  },
  watch: {
    content: {
      handler() {
        if (Array.isArray(this.content)) {
          this.$emit("input", this.content.map(item => item.id).join(','));
        } else if (this.content) {
          this.$emit("input", this.content.id);
        } else {
          this.$emit("input", '');
        }
      }
    },
    options: {
      handler(newValue, oldValue) {
        if (!_.isEqual(newValue, oldValue) && !_.isEmpty(this.value)) {
          this.setUpOptions();
        }
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
      }
    },
    setCurrentProject() {
      if (!this.projectId) {
        return;
      }

      this.currentProject = this.options.find(project => project.id == this.projectId);

      if (this.currentProject) {
        this.content = [this.currentProject];
      }
    },
    completeSelectedLoading(content) {
      this.loading = false;
      this.content.splice(0);
      this.content.push(...content);
    },
    getOptionData(id, content) {
      const option = this.options.concat(this.content).find(item => item.id == id);
      if (option) {
        this.loading--;
        content.push(option);
        this.handleCompleteSelectedLoading(content);
        return;
      }
      ProcessMaker.apiClient
        .get(this.apiGet)
        .then(response => {
          this.loading--;
          content.push(response.data.data);
          this.handleCompleteSelectedLoading(content);
        })
        .catch(error => {
          this.loading--;
          if (error.response.status === 404) {
            this.error = this.$t('Selected not found');
          }
          this.handleCompleteSelectedLoading(content);
        });
    },
    load(filter) {
      ProcessMaker.apiClient
        .get(this.apiList + "?order_direction=asc&status=active&per_page=1000" + (typeof filter === 'string' ? '&filter=' + filter : ''))
        .then(response => {
          this.loading = false;
          this.options = response.data.data;

          if (!this.initialLoadExecuted) {
            this.setCurrentProject(this.options);
            this.initialLoadExecuted = true;
          }
        })
        .catch(err => {
          this.loading = false;
        });
    },
    handleCompleteSelectedLoading(content) {
      if (!this.loading) {
        this.completeSelectedLoading(content)
      }
    }
  },
  mounted() {
    this.load();
  },
};
</script>
  
<style lang="scss" scoped>
@import "~@processmaker/vue-multiselect/dist/vue-multiselect.min.css";
</style>
  