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
    <div
      v-else
      class="font-italic text-black-50"
    >
      Empty
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      @hidden="onModalHidden"
    >
      <template
        #modal-header
        class="d-block"
      >
        <div>
          <h5
            v-if="setting.name"
            class="mb-0"
          >
            {{ $t(setting.name) }}
          </h5>
          <h5
            v-else
            class="mb-0"
          >
            {{ setting.key }}
          </h5>
          <small
            v-if="setting.helper"
            class="form-text text-muted"
          >{{ $t(setting.helper) }}</small>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="close"
          @click="onCancel"
        >
          ×
        </button>
      </template>
      <vue-form-renderer
        v-if="screen('config')"
        v-model="transformed"
        :config="screen('config')"
        :custom-css="screen('custom_css')"
        :computed="screen('computed')"
      />
      <div
        v-else
        class="alert alert-danger"
      >
        {{ $t('Unable to display setting.') }}
      </div>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex"
      >
        <button
          type="button"
          class="btn btn-outline-secondary ml-auto"
          @click="onCancel"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-3"
          :disabled="! changed"
          @click="onSave"
        >
          {{ $t('Save') }}
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ["value", "setting"],
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
        return "secondary";
      }
      return "success";
    },
    changed() {
      return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    },
  },
  watch: {
    value: {
      handler(value) {
        this.input = value;
      },
    },
  },
  mounted() {
    this.input = this.copy(this.value);
    this.preview = this.copy(this.value);
    this.transformed = this.copy(this.input);
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
