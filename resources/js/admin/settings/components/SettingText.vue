<template>
  <div class="setting-text">
    <div
      v-if="input === null || !input.length"
      class="font-italic text-black-50"
    >
      Empty
    </div>
    <div v-else>
      <template v-if="! ui('sensitive')">
        {{ input }}
      </template>
      <template v-else>
        {{ hidden }}
      </template>
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
      <template v-if="! ui('sensitive')">
        <b-form-group :invalid-feedback="invalidFeedback">
          <b-form-input
            ref="input"
            v-model="transformed"
            spellcheck="false"
            autocomplete="off"
            type="text"
            :state="state"
            @keyup.enter="onSave"
          />
        </b-form-group>
      </template>
      <template v-else>
        <b-input-group>
          <b-form-input
            ref="input"
            v-model="transformed"
            class="border-right-0"
            spellcheck="false"
            :type="type"
            @keyup.enter="onSave"
          />
          <b-input-group-append>
            <b-button
              :aria-label="$t('Toggle Show Password')"
              variant="secondary"
              @click="togglePassword"
            >
              <i
                class="fas"
                :class="icon"
              />
            </b-button>
          </b-input-group-append>
        </b-input-group>
      </template>
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
  props: {
    value: {
      type: [String, Number],
      default: null,
    },
    setting: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      input: null,
      showModal: false,
      transformed: null,
      type: "password",
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
    icon() {
      if (this.type === "password") {
        return "fa-eye";
      }
      return "fa-eye-slash";
    },
    hidden() {
      return "•".repeat(this.input.length);
    },
    state() {
      if (this.setting?.ui?.isNotEmpty) {
        return this.transformed !== "" && this.transformed !== null;
      }

      return true;
    },
    invalidFeedback() {
      if (this.setting?.ui?.isNotEmpty && (this.transformed === "" || this.transformed === null)) {
        return this.$t("The current value is empty but a value is required. Please provide a valid value.");
      }
      return "";
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
    if (this.value === null) {
      this.input = "";
    } else {
      this.input = this.value;
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
      this.type = "password";
      this.transformed = this.copy(this.input);
    },
    onModalShown() {
      this.$refs.input.focus();
    },
    onSave() {
      if (this.setting.ui?.isNotEmpty && (this.transformed === "" || this.transformed === null)) {
        return;
      }
      this.input = this.copy(this.transformed);
      this.showModal = false;
      this.emitSaved(this.input);
    },
    togglePassword() {
      if (this.type === "text") {
        this.type = "password";
      } else {
        this.type = "text";
      }
      this.$refs.input.focus();
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
