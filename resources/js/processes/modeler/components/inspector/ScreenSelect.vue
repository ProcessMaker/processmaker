<template>
    <div class="form-group">
        <label>{{ $t(label)}}</label>
        <multiselect v-model="content"
                     track-by="id"
                     label="title"
                     :loading="loading"
                     :placeholder="$t('type here to search')"
                     :options="screens"
                     :multiple="false"
                     :show-labels="false"
                     :searchable="true"
                     :internal-search="false"
                     @open="load"
                     @search-change="load">
        </multiselect>
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
        screens: []
      };
    },
    computed: {
      node() {
        return this.$parent.$parent.highlightedNode.definition;
      }
    },
    mounted() {
      this.loadValue();
    },
    watch: {
      content: {
        handler() {
          this.$emit("input", this.content.id);
        }
      },
      value: {
        handler() {
          this.loadValue();
        }
      }
    },
    methods: {
      loadValue() {
        // Load selected item.
        if (!this.content && this.value) {
          this.loading = true;
          ProcessMaker.apiClient
            .get("screens/"+ this.value)
            .then(response => {
              this.content = response.data;
              this.loading = false;
            });
        }
      },
      load(filter) {
        let params = Object.assign(
          {
            type: 'FORM',
            order_direction : 'asc',
            status: 'active',
            filter : (typeof filter === 'string' ? filter : '')
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
