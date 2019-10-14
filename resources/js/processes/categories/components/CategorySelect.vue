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
          if (value) {
            const content = [];
            const selected = value.split(',');
            let loading = selected.length;
            this.loading = true;
            selected.forEach(category => {
              loading = this.getOptionData(category, loading, content);
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
      getOptionData(id, loading, content) {
        const option = this.options.find(item => item.id == id);
        if (option) {
          loading--;
          content.push(option);
          (!loading) ? this.completeSelectedLoading(content) : null;
          return loading;
        }
        ProcessMaker.apiClient
          .get(this.apiGet + "/" + category)
          .then(response => {
            loading--;
            content.push(response.data);
            (!loading) ? this.completeSelectedLoading(content) : null;
          })
          .catch(error => {
            loading--;
            if (error.response.status === 404) {
              this.error = this.$t('Selected not found');
            }
            (!loading) ? this.completeSelectedLoading(content) : null;
          });
        return loading;
      },
      load(filter) {
        ProcessMaker.apiClient
          .get(this.apiList + "?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
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
