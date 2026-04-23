<template>
  <div>
    <b-modal
      v-model="shown"
      :title="title"
      @hidden="onModalHidden"
    >
      <div>
        <p>{{ $t('Please upload a settings file that was previously exported from ProcessMaker 4.') }}</p>
        <p>{{ $t('All settings that are present on this system will be updated with your imported values.') }}</p>
        <h5
          v-if="imported"
          class="text-center pt-3 pb-2"
        >
          <i class="fas fa-check-circle text-success" /> {{ $t("\{\{ count \}\} \{\{ plural \}\} updated.", {count: total, plural: plural}) }}
        </h5>
        <h5
          v-else-if="loading"
          class="text-center pt-3 pb-2"
        >
          <i class="fas fa-cog fa-spin text-secondary" /> Importing...
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
            accept=".json"
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
            @click="onClose"
          >
            {{ $t('OK') }}
          </button>
        </template>
        <template v-else>
          <button
            type="button"
            class="btn btn-outline-secondary ml-auto"
            :disabled="loading"
            @click="onClose"
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
export default {
  props: ["group"],
  data() {
    return {
      changed: false,
      loading: false,
      imported: false,
      shown: false,
      total: 0,
      url: "/settings/import",
    };
  },
  computed: {
    title() {
      return this.$t("Import Settings");
    },
    disabled() {
      if (this.loading) {
        return true;
      }

      if (!this.changed) {
        return true;
      }

      return false;
    },
    plural() {
      if (this.total == 1) {
        return this.$t("setting was");
      }
      return this.$t("settings were");
    },
  },
  methods: {
    onClose() {
      this.shown = false;
    },
    onModalHidden() {
      if (this.imported) {
        this.$emit("import");
      }
      this.loading = false;
      this.changed = false;
    },
    onImport() {
      this.loading = true;
      const formData = new FormData();
      formData.append("file", this.file);
      ProcessMaker.apiClient.post(
        this.url,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        },
      ).then((response) => {
        this.imported = true;
        this.total = response.data.meta.total;
      })
        .catch((error) => {
          console.log("ERROR", error);
          let message = this.$t("The file was not imported.");
          if (error.response && error.response.data && error.response.data.error) {
            message += " ";
            message += this.$t(error.response.data.error);
          }
          ProcessMaker.alert(message, "danger");
          this.loading = false;
        });
    },
    handleFile(file) {
      this.file = this.$refs.file.files[0];
      this.changed = true;
    },
    show() {
      this.shown = true;
    },
  },
};
</script>

<style lang="scss">
@import '../../../../sass/colors';
</style>
