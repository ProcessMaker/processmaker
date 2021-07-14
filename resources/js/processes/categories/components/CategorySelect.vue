<template>
  <div class="form-group" required>
    <label>{{ $t(label) }}</label>
    <multiselect v-model="content"
                 :aria-label="$t(label)"
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
                 @open="load()"
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
  export default {
    props: ["value", "errors", "label", "helper", "params", "apiGet", "apiList"],
    data() {
      return {
        content: [],
        loading: false,
        options: [],
        error: '',
        uncategorizedIdSet: false
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
        this.loadUncategorized();
        ProcessMaker.apiClient
          .get(this.apiList + "?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            this.options = response.data.data;
          })
          .catch(err => {
            this.loading = false;
          });
      },
      loadUncategorized() {
        if (this.uncategorizedIdSet) {
          return;
        }
        ProcessMaker.apiClient
          .get(this.apiList + "?filter=Uncategorized&per_page=1&order_by=id&order_direction=ASC")
          .then(response => {
            this.content = response.data.data;
            this.uncategorizedIdSet = true;
          });
      },
      resetUncategorized() {
        this.uncategorizedIdSet = false;
        this.loadUncategorized();
      }
    }
  };
</script>

<style lang="scss" scoped>
  @import "~vue-multiselect/dist/vue-multiselect.min.css";
</style>
