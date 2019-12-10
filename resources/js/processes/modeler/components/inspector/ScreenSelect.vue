<template>
    <div class="form-group" :class="{'has-error':error}">
        <label>{{ $t(label) }}</label>
        <multiselect v-model="content"
                     track-by="id"
                     label="title"
                     :class="{'border border-danger':error}"
                     :loading="loading"
                     :placeholder="$t('type here to search')"
                     :options="screens"
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
    props: ["value", "label", "helper", "params", "requiredMessage"],
    components: {
      Multiselect
    },
    data() {
      return {
        content: "",
        loading: false,
        screens: [],
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
        immediate: true,
        handler() {
          if (this.content) {
            this.error = '';
            this.$emit("input", this.content.id);
          } else if (this.requiredMessage) {
            this.error = this.requiredMessage
          }
        }
      },
      value: {
        immediate: true,
        handler() {
          // Load selected item.
          if (this.value) {
            this.loading = true;
            ProcessMaker.apiClient
              .get("screens/" + this.value)
              .then(response => {
                this.loading = false;
                this.content = response.data;
              })
              .catch(error => {
                this.loading = false;
                if (error.response.status == 404) {
                  this.content = '';
                  this.error = this.$t('Selected screen not found');
                }
              });
          } else {
            this.content = '';
            if (this.requiredMessage) {
              this.error = this.requiredMessage
            } else {
              this.error = '';
            }
          }
        },
      }
    },
    methods: {
      type() {
        if (this.params && this.params.type) {
          return this.params.type
        }
        return 'FORM'
      },
      load(filter) {
        let params = Object.assign(
          {
            type: this.type(),
            order_direction: 'asc',
            status: 'active',
            filter: (typeof filter === 'string' ? filter : '')
          },
          this.params
        );
        this.loading = true;
        ProcessMaker.apiClient
          .get('screens', {
            params: params
          })
          .then(response => {
            this.loading = false;
            this.screens = response.data.data;
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
