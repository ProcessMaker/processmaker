<template>
  <div class="setting-text">
    <div
      v-if="input === null || !input.length"
      class="font-italic text-black-50"
    >
      Empty
    </div>
    <div v-else>
      {{ trimmed(input) }}
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      @hidden="onModalHidden"
      @shown="onModalShown"
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
      <b-form-textarea
        ref="input"
        v-model="transformed"
        rows="10"
        spellcheck="false"
      />
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
      input: null,
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
    if (typeof this.value === "object" || typeof this.value === "array") {
      this.input = JSON.stringify(this.value, null, 2);
    } else {
      this.input = this.value;
    }

    if (this.input == "null" || this.input === null) {
      this.input = "";
    }

    this.transformed = this.copy(this.input);
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
    onModalShown() {
      this.$refs.input.focus();
    },
    onSave() {
      this.input = this.copy(this.transformed);
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
