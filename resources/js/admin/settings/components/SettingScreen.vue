<template>
  <div class="setting-text">
    <div v-if="previewScreen('config')">
      <vue-form-renderer
        v-model="preview"
        :config="previewScreen('config')"
        :computed="previewScreen('computed')"
        class="preview-renderer"
      />
    </div>
    <div v-else class="font-italic text-black-50">
      Empty
    </div>
    <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden">
      <template v-slot:modal-header class="d-block">
        <div>
          <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
          <h5 class="mb-0" v-else>{{ setting.key }}</h5>
          <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
        </div>
        <button type="button" aria-label="Close" class="close" @click="onCancel">×</button>
      </template>
      <vue-form-renderer
        v-if="screen('config')"
        v-model="transformed"
        :config="screen('config')"
        :custom-css="screen('custom_css')"
        :computed="screen('computed')"
      />
      <div v-else class="alert alert-danger">{{ $t('Unable to display setting.') }}</div>
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
import {VueFormRenderer} from "@processmaker/screen-builder";

export default {
  mixins: [settingMixin],
  props: ['value', 'setting'],
  data() {
    return {
      config: null,
      formData: null,
      input: null,
      preview: null,
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
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    }
  },
  methods: {
    screen(property) {
      return _.get(this, `setting.ui.screen.screens.0.${property}`, null);
    },
    previewScreen(property) {
      return _.get(this, `setting.ui.preview.screens.0.${property}`, null);
    },
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
      this.preview = this.copy(this.transformed);
      this.showModal = false;
      this.emitSaved(this.input);
    },
  },
  mounted() {
    this.input = this.copy(this.value);
    this.preview = this.copy(this.value);
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
