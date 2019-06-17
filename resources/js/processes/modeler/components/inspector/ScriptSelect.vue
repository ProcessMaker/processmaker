<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <multiselect v-model="content"
                     track-by="id"
                     label="title"
                     :class="{'border border-danger':error}"
                     :loading="loading"
                     :placeholder="$t('type here to search')"
                     :options="scripts"
                     :multiple="false"
                     :show-labels="false"
                     :searchable="true"
                     :internal-search="false"
                     @open="load"
                     @search-change="load">
            <template slot="noResult" >
                {{ $t('No elements found. Consider changing the search query.') }}
            </template>
            <template slot="noOptions" >
                {{ $t('No Data Available') }}
            </template>
        </multiselect>
        <small v-if="error" class="text-danger">{{ error }}</small>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    </div>
</template>


<script>
  import Multiselect from "vue-multiselect";

  export default {
    props: ["value", "label", "helper", "params"],
    components: {
      Multiselect
    },
    data() {
      return {
        content: "",
        loading: false,
        scripts: [],
        error: ''
      };
    },
    computed: {
      node() {
        return this.$parent.$parent.highlightedNode.definition;
      }
    },
    watch: {
      content: {
        handler() {
          this.$emit("input", this.content.id);
        }
      },
      value: {
        immediate: true,
        handler() {
          // Load selected item.
          if (this.value) {
            this.loading = true;
            ProcessMaker.apiClient
              .get("scripts/"+ this.value)
              .then(response => {
                this.loading = false;
                this.content = response.data;
              })
              .catch(error => {
                this.loading = false;
                if (error.response.status == 404) {
                  this.content = '';
                  this.error = this.$t('Selected script not found');
                }
              });
          } else {
            this.content = '';
            this.error = '';
          }
        },
      }
    },
    methods: {
      load(filter) {
        this.loading = true;
        ProcessMaker.apiClient
          .get("scripts?order_direction=asc" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            this.scripts = response.data.data;
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
