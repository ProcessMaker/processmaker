<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <multiselect v-model="content"
                     ref="screen-select"
                     track-by="id"
                     label="title"
                     :class="{'is-invalid':error}"
                     :loading="loading"
                     :placeholder="placeholder || $t('type here to search')"
                     :options="screens"
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
                :href="`/designer/screen-builder/${content.id}/edit`"
                target="_blank"
        >
            {{ $t('Open Screen') }}
            <i class="ml-1 fas fa-external-link-alt"/>
        </a>
    </div>
</template>

<script>
  import Multiselect from "vue-multiselect";

  export default {
    props: ["value", "label", "helper", "params", "required", "placeholder"],
    components: {
      Multiselect
    },
    data() {
      return {
        content: "",
        loading: false,
        screens: [],
        error: null
      };
    },
    watch: {
     content: {
        immediate: true,
        handler() {
          this.validate();
          if (this.content) {
            this.error = '';
            this.$emit('input', this.content.id);
            this.$refs['screen-select'].$el.focus();
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
      },
      validate() {
        if (!this.required || this.value && this.value !== undefined)  {
          return;
        }

        this.error = this.$t('A screen selection is required');
      },
    },
    mounted() {
      this.validate();
    }
  };
</script>

<style lang="scss" scoped>
    @import "~vue-multiselect/dist/vue-multiselect.min.css";
</style>
