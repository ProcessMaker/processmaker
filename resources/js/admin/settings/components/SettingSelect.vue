<template>
    <div class="setting-text">
      <div v-if="input === null || !input.length" class="font-italic text-black-50">
        Empty
        <b-badge v-if="hasAuthorizedBadge" pill :variant="setting.ui.authorizedBadge ? 'success' : 'warning'">
           <span v-if="setting.ui.authorizedBadge">{{ $t('Authorized') }}</span>
           <span v-else>{{ $t('Not Authorized') }}</span>
         </b-badge>
      </div>
      <div v-else>
        {{ display }}
         <b-badge v-if="hasAuthorizedBadge" pill :variant="setting.ui.authorizedBadge ? 'success' : 'warning'">
           <span v-if="setting.ui.authorizedBadge">{{ $t('Authorized') }}</span>
           <span v-else>{{ $t('Not Authorized') }}</span>
         </b-badge>
      </div>
      <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden">
        <template v-slot:modal-header class="d-block">
          <div>
            <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
            <h5 class="mb-0" v-else>{{ setting.key }}</h5>
            <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
          </div>
          <button type="button" :aria-label="$t('Close')" class="close" @click="onCancel">×</button>
        </template>
        <div>
          <b-form-group>
            <multiselect
                v-model="transformed"
                :placeholder="$t('Type to search')"
                :options="options"
                :multiple="true"
                :show-labels="false"
                :searchable="true"
                :track-by="setting.ui.trackBy"
                :label="setting.ui.label"
            >
            </multiselect>
            <!-- <b-form-radio v-for="(option, value) in ui('options')" v-model="transformed" :key="value" :value="value">{{ option }}</b-form-radio> -->
          </b-form-group>
        </div>
        <div slot="modal-footer" class="w-100 m-0 d-flex">
          <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
              {{ $t('Cancel') }}
          </button>
          <button type="button" class="btn btn-secondary ml-3" @click="onSave" :disabled="! changed">
              {{ $t('Save')}}
          </button>
        </div>
      </b-modal>
    </div>
  </template>
  
  <script>
  import settingMixin from "../mixins/setting";
  
  export default {
    mixins: [settingMixin],
    props: ['value', 'setting'],
    data() {
      return {
        input: null,
        selected: null,
        showModal: false,
        transformed: null,
      };
    },
    computed: {
      variant() {
        if (this.disabled) {
          return 'secondary';
        } else {
          return 'success';
        }
      },
      changed() {
        return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
      },
      display() {
        const options = this.ui('options');
        if (!options) {
          return this.input;
        }
        const keys = Object.keys(options);
        
        if (keys.includes(this.input)) {
          return options[this.input];
        } else {
            let display = [];
            for (const [key, objValue] of Object.entries(this.input)) {
                for (const [key, value] of Object.entries(objValue)) {
                    if (key === this.setting.ui.label) {
                        display.push(value);
                    }
                }
            }

          return display.join(', ');;
        }
      },
      hasAuthorizedBadge() {
        if (!this.setting) {
          return false;
        }
        // Prevent authorization badge from showing on 'standard' authentication
        const hasAuthorizedBadge = _.has(this.setting, 'ui.authorizedBadge') && this.setting.config !== '0' ? true : false;
        return hasAuthorizedBadge;
      },
      options() {
        if (this.setting.ui.options) {
           return JSON.parse(this.setting.ui.options);
        } 
        return [];
      }
    },
    watch: {
      value: {
        handler: function(value) {
          this.input = value;
        },
      }
    },
    methods: {
      onCancel() {
        this.showModal = false;
      },
      onEdit() {
        this.showModal = true;
      },
      onModalHidden() {
        this.transformed = this.copy(this.input);
      },
      onSave() {
        this.input = this.copy(this.transformed);
        this.showModal = false;
        this.emitSaved(this.input);
      },
    },
    mounted() {
      if (this.value === null) {
        this.input = '';
      } else {
        this.input = this.value;
      }
      this.transformed = this.copy(this.input);
    }
  };
  </script>
  
  <style lang="scss" scoped>
    @import '../../../../sass/colors';
  
    $disabledBackground: lighten($secondary, 20%);
  
    .btn:disabled,
    .btn.disabled {
      background: $disabledBackground;
      border-color: $disabledBackground;
      opacity: 1 !important;
    }
  </style>
  