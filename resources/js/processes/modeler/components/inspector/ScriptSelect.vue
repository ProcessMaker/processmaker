<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <multiselect v-model="content"
                     track-by="id"
                     label="title"
                     :class="{'is-invalid':error}"
                     :loading="loading"
                     :placeholder="$t('type here to search')"
                     :options="scripts"
                     :multiple="false"
                     :show-labels="false"
                     :searchable="true"
                     :internal-search="false"
                     :required="required"
                     @open="load"
                     @search-change="load">
            <template slot="noResult" >
                {{ $t('No elements found. Consider changing the search query.') }}
            </template>
            <template slot="noOptions" >
                {{ $t('No Data Available') }}
            </template>
        </multiselect>
        <div v-if="error" class="invalid-feedback">
          <div>{{ error }}</div>
        </div>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
        <a
                v-if="content.id"
                :href="`/designer/scripts/${content.id}/builder`"
                target="_blank"
        >
            {{ $t('Open Script') }}
            <i class="ml-1 fas fa-external-link-alt"/>
        </a>
    </div>
</template>


<script>
  import Multiselect from "vue-multiselect";

  export default {
    props: ["value", "label", "helper", "params", 'required'],
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
        return this.$root.$children[0].$refs.modeler.highlightedNode;
      },
      definition() {
        return this.node.definition;
      },
    },
    watch: {
      content: {
        handler() {
          this.validate();
          if (this.content) {
            this.error = '';
            if (this.node) {
              this.$set(this.definition, "scriptRef", this.content.id);
            } else  {
              this.$emit('input', this.content.id);
            }
          }
        }
      },
      value: {
        immediate: true,
        handler() {
          this.validate();
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
          }
        },
      }
    },
    methods: {
      load(filter) {
        let params = Object.assign({
          order_direction: 'asc',
          selectList: true,
          filter:(typeof filter === 'string' ? filter : '')
        });
        this.loading = true;
        ProcessMaker.apiClient
          .get('scripts', {
            params: params
          })
          .then(response => {
            this.loading = false;
            this.scripts = response.data.data;
          })
          .catch(err => {
            this.loading = false;
          });
      },
      checkScriptRefExists() {
        if (this.definition.scriptRef) {
          return;
        }
        this.$set(this.definition, "scriptRef", '');
      },
      validate() {
        if (!this.required || this.value && this.value !== undefined)  {
          return;
        }

        this.error = this.$t('A script selection is required');
      }
    },
    mounted() {
      if (this.node) {
        this.checkScriptRefExists();  
      }
      
      this.validate();
    }
  };
</script>

<style lang="scss" scoped>
    @import "~vue-multiselect/dist/vue-multiselect.min.css";
</style>
