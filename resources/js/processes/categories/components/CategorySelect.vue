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
        uncategorizedCategory: null,
        lastSelectedId: null,
      };
    },
    computed: {

    },
    watch: {
      content: {
        handler() {
          this.setUncategorizedDefault();
          this.$emit("input", this.content instanceof Array ? this.content.map(item => item.id).join(',') : (this.content ? this.content.id : ''));
          this.$emit("update:duplicateScreenCategory", this.content);
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
          selected.forEach(category => {
            this.getOptionData(category, content);
          });
        } else {
          this.content.splice(0);
          this.setUncategorizedDefault();
        }
      },
      completeSelectedLoading(content) {
        this.loading = false;
        this.content.splice(0);
        this.content.push(...content);
        this.setUncategorizedDefault();
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
            this.options = response.data.data;
          })
          .catch(err => {
            this.loading = false;
          });
      },
      loadUncategorized() {
        return ProcessMaker.apiClient
          .get(this.apiList + "?filter=Uncategorized&per_page=1&order_by=id&order_direction=ASC")
          .then(response => {
            this.uncategorizedCategory = response.data.data[0];
          });
      },
      setUncategorizedDefault() {
        if (!this.uncategorizedCategory) {
          return;
        }

        if (this.content.length === 0) {
          // No categories so give it the Uncategorized category
          this.content.push(this.uncategorizedCategory);
          return;
        }

        if (this.lastSelectedId === this.uncategorizedCategory.id) {
          // The user picked Uncategorized so remove all other categories
          this.lastSelectedId = null;
          this.content = [this.uncategorizedCategory];
          return;
        }

        const uncategorizedCategoryIndex = this.content.findIndex(c => {
          return c.id === this.uncategorizedCategory.id;
        });
        if (uncategorizedCategoryIndex >= 0 && this.content.length > 1) {
          // The use picked a category so remove Uncategorized
          this.content.splice(uncategorizedCategoryIndex, 1);
        }
      },
    },
    mounted() {
      this.loadUncategorized().then(() => {
        this.setUpOptions();
      });
    }
  };
</script>

<style lang="scss" scoped>
  @import "~@processmaker/vue-multiselect/dist/vue-multiselect.min.css";
</style>
