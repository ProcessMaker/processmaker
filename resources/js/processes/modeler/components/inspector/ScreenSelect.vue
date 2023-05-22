<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <multiselect :aria-label="$t(label)"
                     v-model="content"
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
                     @open="load()"
                     @search-change="load">
            <template slot="noResult" >
                {{ $t('No elements found. Consider changing the search query.') }}
            </template>
            <template slot="noOptions" >
                {{ $t('No Data Available') }}
            </template>
        </multiselect>
        <div v-if="error" class="invalid-feedback" role="alert">
          <div>{{ error }}</div>
        </div>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
        <a
                v-if="content && content.id"
                :href="`/designer/screen-builder/${content.id}/edit`"
                target="_blank"
        >
            {{ $t('Open Screen') }}
            <i class="ml-1 fas fa-external-link-alt"/>
        </a>
    </div>
</template>

<script>
  export default {
    props: ["value", "label", "helper", "params", "required", "placeholder"],
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
        handler() {
          this.validate();
          let selected = ''
          if (this.content) {
            this.error = '';
            selected = this.content.id;
          }
          this.$emit('input', selected);
        }
      },
      value: {
        handler() {
          // Load selected item.
          if (this.value) {
            if (!(this.content && this.content.id === this.value)) {
              this.loadScreen(this.value);
            }
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
      interactive() {
        if (this.params && this.params.interactive) {
          return this.params.interactive;
        }
        return false;
      },
      loadScreen(value) {
        this.loading = true;
        ProcessMaker.apiClient
          .get("screens/" + value)
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
      },
      load(filter) {
        let params = Object.assign(
          {
            type: this.type(),
            interactive: this.interactive(),
            order_direction: 'asc',
            status: 'active',
            selectList: true,
            filter: (typeof filter === 'string' ? filter : '')
          },
          this.params
        );
        this.loading = true;
        ProcessMaker.apiClient
          .get('screens?exclude=config', {
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
      if (this.value) {
        this.loadScreen(this.value);
      }
    }
  };
</script>

<style lang="scss" scoped>
    @import "~@processmaker/vue-multiselect/dist/vue-multiselect.min.css";
</style>
