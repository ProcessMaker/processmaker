<template>
  <div class="setting-text">
    <div
      v-if="input === null || !input.length"
      class="font-italic text-black-50"
    >
      Empty
    </div>
    <div v-else>
      {{ display }}
      <b-badge
        v-if="hasAuthorizedBadge"
        pill
        :variant="setting.ui.authorizedBadge ? 'success' : 'warning'"
      >
        <span v-if="setting.ui.authorizedBadge">{{ $t('Authorized') }}</span>
        <span v-else>{{ $t('Not Authorized') }}</span>
      </b-badge>
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
      <div>
        <b-form-group>
          <b-form-radio
            v-for="(option, value) in ui('options')"
            :key="value"
            v-model="transformed"
            :value="value"
          >
            {{ option }}
          </b-form-radio>
        </b-form-group>
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
import _ from "lodash";
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ["value", "setting"],
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
        return "secondary";
      }
      return "success";
    },
    changed() {
      return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    },
    display() {
      const options = this.ui("options");
      if (!options) {
        return this.input;
      }
      const keys = Object.keys(options);
      if (keys.includes(this.input)) {
        return options[this.input];
      }
      return this.input;
    },
    hasAuthorizedBadge() {
      if (!this.setting) {
        return false;
      }
      // Prevent authorization badge from showing on 'standard' authentication
      const hasAuthorizedBadge = !!(_.has(this.setting, "ui.authorizedBadge") && this.setting.config !== "0");
      return hasAuthorizedBadge;
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
      this.transformed = this.copy(this.input);
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
