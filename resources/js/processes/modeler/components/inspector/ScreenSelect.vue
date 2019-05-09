<template>
    <div class="form-group" :class="{'has-error':error}">
        <label>{{ $t(label)}}</label>
        <div v-if="loading">{{ $t('Loading...') }}</div>
        <div v-else>
            <multiselect v-model="content"
                         track-by="id"
                         label="title"
                         :class="{'border border-danger':error}"
                         :placeholder="$t('type here to search')"
                         :options="screens"
                         :multiple="false"
                         :show-labels="false"
                         :searchable="true"
                         :internal-search="false"
                         :helper="helper"
                         @search-change="load">
            </multiselect>
            <small v-if="error" class="text-danger">{{error}}</small>
        </div>
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
        loading: true,
        screens: [],
        error: '',
      };
    },
    computed: {
      node() {
        return this.$parent.$parent.highlightedNode.definition;
      }
    },
    mounted() {
      this.load();
    },
    watch: {
      content: {
        handler() {
          if (this.content) {
            this.error = '';
            this.$emit("input", this.content.id);
          }
        }
      },
      value: {
        immediate: true,
        handler() {
          // Load selected item.
          if (this.value) {
            ProcessMaker.apiClient
              .get("screens/"+ this.value)
              .then(response => {
                this.content = response.data;
              })
              .catch(error => {
                if (error.response.status == 404) {
                  this.content = '';
                  this.error = this.$t('Selected screen not found');
                }
              });
          } else {
            this.content = '';
            this.error = '';
          }
        },
      },
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
            order_direction : 'asc',
            status: 'active',
            filter : (typeof filter === 'string' ? filter : '')
          },
          this.params
        );
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
