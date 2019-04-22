<template>
    <div class="form-group">
        <label>{{ $t(label)}}</label>
        <div v-if="loading">{{ $t('Loading...') }}</div>
        <div v-else>
            <multiselect v-model="content"
                         track-by="id"
                         label="title"
                         :placeholder="$t('type here to search')"
                         :options="scripts"
                         :multiple="false"
                         :show-labels="false"
                         :searchable="true"
                         :internal-search="false"
                         :helper="helper"
                         @search-change="load">
            </multiselect>
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
        scripts: []
      };
    },
    computed: {
      node() {
        return this.$parent.$parent.highlightedNode.definition;
      }
    },
    mounted() {
      // Load selected item.
      if (!this.content && this.value) {
        ProcessMaker.apiClient
          .get("scripts/"+ this.value)
          .then(response => {
            this.content = response.data;
          });
      }
      this.load();
    },
    watch: {
      content: {
        handler() {
          this.$emit("input", this.content.id);
        }
      }
    },
    methods: {
      load(filter) {
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
