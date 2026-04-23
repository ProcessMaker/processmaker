<template>
  <div class="setting-text">
    <div
      v-if="!!input"
      class="font-italic text-black-50"
    >
      {{ input }}
    </div>
    <div v-else>
      {{ $t('Empty') }}
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
      <template>
        <div class="d-flex align-items-center w-100">
          <b-form-input
            id="imap-polling-interval"
            ref="input"
            v-model="transformed"
            class="w-25"
            type="number"
            min="1"
            max="60"
            name="interval"
          />
          <b-form-input
            v-model="transformed"
            type="range"
            min="1"
            max="60"
            class="ml-3 w-100"
          />
        </div>
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
  props: ["value", "setting"],
  data() {
    return {
      input: null,
      showModal: false,
      transformed: null,
    };
  },
  computed: {
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
    onModalShown() {
      this.$refs.input.focus();
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

<style>

</style>
