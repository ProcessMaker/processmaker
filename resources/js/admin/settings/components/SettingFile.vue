<template>
  <div class="setting-text">
    <div
      v-if="value == null || !value.length"
      class="font-italic text-black-50"
    >
      Empty
    </div>
    <div v-else>
      {{ value }}
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      @hidden="onModalHidden"
      @shown="onModalShown"
    >
      <div>
        <p>{{ $t('Please upload a settings file.') }}</p>
        <h5
          v-if="imported"
          class="text-center pt-3 pb-2"
        >
          <i class="fas fa-check-circle text-success" /> {{ $t("File uploaded") }}
        </h5>
        <h5
          v-else-if="loading"
          class="text-center pt-3 pb-2"
        >
          <i class="fas fa-cog fa-spin text-secondary" /> {{ $t("Uploading...") }}
        </h5>
        <p
          v-else
          class="text-center"
        >
          <input
            id="settings-import-file"
            ref="file"
            type="file"
            class="d-none"
            :aria-label="$t('Select a file')"
            @change="handleFile"
          >
          <button
            type="button"
            class="btn btn-secondary"
            @click="$refs.file.click()"
          >
            <i class="fas fa-upload" />
            {{ $t('Browse') }}
          </button>
        </p>
      </div>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex"
      >
        <template v-if="imported">
          <button
            type="button"
            class="btn btn-secondary ml-auto"
            @click="onSave"
          >
            {{ $t('OK') }}
          </button>
        </template>
        <template v-else>
          <button
            type="button"
            class="btn btn-outline-secondary ml-auto"
            :disabled="loading"
            @click="onCancel"
          >
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-secondary ml-3"
            :disabled="disabled"
            @click="onImport"
          >
            {{ $t('Import') }}
          </button>
        </template>
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
      changed: false,
      loading: false,
      imported: false,
      input: null,
      showModal: false,
      transformed: null,
    };
  },
  computed: {
    disabled() {
      if (this.loading) {
        return true;
      }

      if (!this.changed) {
        return true;
      }

      return false;
    },
    variant() {
      if (this.disabled) {
        return "secondary";
      }
      return "success";
    },
    // changed() {
    //   return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    // }
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
    handleFile(file) {
      this.file = this.$refs.file.files[0];
      this.changed = true;
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
    onModalShown() {
      this.changed = false;
      this.loading = false;
      this.imported = false;
    },
    onSave() {
      this.showModal = false;
      this.emitSaved(this.file.name);
    },
    onImport() {
      this.loading = true;
      const formData = new FormData();
      formData.append("file", this.file);
      formData.append("setting_key", this.setting.key || "services.ldap.certificate");
      ProcessMaker.apiClient.post(
        "settings/upload-file",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        },
      ).then((response) => {
        this.imported = true;
        this.changed = true;
      })
        .catch((error) => {
          if (error.response && error.response.data && error.response.data.error) {
            message = this.$t(error.response.data.error);
            ProcessMaker.alert(message, "danger");
          }
          this.loading = false;
        });
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
