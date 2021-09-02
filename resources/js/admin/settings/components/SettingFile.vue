<template>
  <div class="setting-text">
    <div v-if="value == null || !value.length" class="font-italic text-black-50">
      Empty
    </div>
    <div v-else>
      {{value}}
    </div>
    <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden" @shown="onModalShown">
      <div>
        <p>{{ $t('Please upload a settings file.') }}</p>
        <h5 class="text-center pt-3 pb-2" v-if="imported">
          <i class="fas fa-check-circle text-success"></i> {{ $t("File uploaded") }}
        </h5>
        <h5 class="text-center pt-3 pb-2" v-else-if="loading">
          <i class="fas fa-cog fa-spin text-secondary"></i> {{ $t("Uploading...")}}
        </h5>
        <p class="text-center" v-else>
          <input id="settings-import-file" type="file" ref="file" class="d-none" @change="handleFile" :aria-label="$t('Select a file')">
          <button type="button" @click="$refs.file.click()" class="btn btn-secondary">
            <i class="fas fa-upload"></i>
            {{ $t('Browse')}}
          </button>
        </p>
      </div>
      <div slot="modal-footer" class="w-100 m-0 d-flex">
        <template v-if="imported">
          <button type="button" class="btn btn-secondary ml-auto" @click="onSave">
            {{ $t('OK')}}
          </button>
        </template>
        <template v-else>
          <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel" :disabled="loading">
            {{ $t('Cancel') }}
          </button>
          <button type="button" class="btn btn-secondary ml-3" @click="onImport" :disabled="disabled">
            {{ $t('Import')}}
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
  props: ['value', 'setting'],
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

      if (! this.changed) {
        return true;
      }

      return false;
    },
    variant() {
      if (this.disabled) {
        return 'secondary';
      } else {
        return 'success';
      }
    },
    // changed() {
    //   return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    // }
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    }
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
      let formData = new FormData();
      formData.append('file', this.file);
      formData.append('setting_key', this.setting.key || 'services.ldap.certificate');
      ProcessMaker.apiClient.post('settings/upload-file',
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
      ).then(response => {
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
  mounted() {
    if (typeof this.value == 'object' || typeof this.value == 'array') {
      this.input = JSON.stringify(this.value, null, 2);
    } else {
      this.input = this.value;
    }

    if (this.input == "null" || this.input === null) {
      this.input = '';
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
