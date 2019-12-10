<template>
  <div class="form-group">
    <label>{{ $t(label) }}</label>
    <multiselect v-model="content"
                 track-by="id"
                 label="name"
                 :class="{'border border-danger':error}"
                 :loading="!!loading"
                 :placeholder="$t('type here to search')"
                 :options="options"
                 :multiple="true"
                 :show-labels="false"
                 :searchable="true"
                 :internal-search="false"
                 @open="load"
                 @search-change="load">
      <template slot="noResult">
        {{ $t('No elements found. Consider changing the search query.') }}
      </template>
      <template slot="noOptions">
        {{ $t('No Data Available') }}
      </template>
    </multiselect>
    <div class="invalid-feedback d-block" v-for="(error, index) in errors" :key="index">
      <small v-if="error" class="text-danger">{{ error }}</small>
    </div>
    <small v-if="error" class="text-danger">{{ error }}</small>
    <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
  </div>
</template>


<script>
  import Multiselect from "vue-multiselect";

  export default {
    props: ["value", "errors", "label", "helper", "params", "apiGet", "apiList"],
    components: {
      Multiselect
    },
    data() {
      return {
        content: [],
        loading: false,
        firstSelect: true,
        options: [],
        error: ''
      };
    },
    computed: {

    },
    watch: {
      content: {
        handler(value) {
          this.$emit("input", this.content instanceof Array ? this.content.map(item => item.id).join(',') : (this.content ? this.content.id : ''));
          this.$emit("update:duplicateScreenCategory", this.content);
        }
      },
      value: {
        immediate: true,
        handler(value) {
          if (!value) {
            this.load();
          }
          if (value) {
            const content = [];
            const selected = String(value).split(',');
            this.loading = selected.length;
            selected.forEach(category => {
              this.getOptionData(category, content);
            });
          } else {
            this.content.splice(0);
          }
        },
      }
    },
    methods: {
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
          (!this.loading) ? this.completeSelectedLoading(content) : null;
          return;
        }
        ProcessMaker.apiClient
          .get(this.apiGet + "/" + id)
          .then(response => {
            this.loading--;
            content.push(response.data);
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
            if (this.firstSelect && _.size(response.data.data) === 1) {
              this.firstSelect = false;
              this.content = response.data.data;
            }
            this.options = response.data.data;
          })
          .catch(err => {
            this.loading = false;
          });
      }
    }
  };
</script>

<style lang="scss" scoped>
  @import "~vue-multiselect/dist/vue-multiselect.min.css";
</style>
