<template>
  <b-modal
    id="bv-modal-whitelist"
    ref="bv-modal-whitelist"
    hide-footer
  >
    <template #modal-title>
      <div class="tw-self-stretch tw-text-base tw-font-medium tw-text-[#20242A]">
        {{ $t("Configure URL Parents for Embedding") }}
      </div>
      <div class="tw-self-stretch tw-text-sm tw-text-[#596372]">
        {{
          $t("Please provide a valid URL (e.g., https://example.com ) to specify the allowed origin(s) permitted to embed ProcessMaker.")
        }}
      </div>
    </template>
    <div class="tw-block">
      <div class="form-group col-md-12">
        <div class="d-flex flex-column">
          <label for="site-name">
            {{ $t("Site Name") }}
          </label>
          <b-form-input
            id="site-name"
            v-model="siteName"
            type="text"
            required
            :state="stateSiteName"
            :placeholder="$t('Site Name')"
          />
        </div>
      </div>
      <div class="form-group col-md-12">
        <div class="d-flex flex-column">
          <label for="url">
            {{ $t("Url") }}
          </label>
          <b-form-input
            id="url"
            v-model="url"
            type="text"
            required
            placeholder="https://www.sample.org/head"
            :state="stateURL"
          />
          <div
            v-if="urlError"
            class="text-danger mt-1"
          >
            {{ urlError }}
          </div>
        </div>
      </div>
    </div>
    <div class="tw-block tw-mt-8">
      <b-button
        id="confirm"
        type="submit"
        variant="primary"
        block
        @click="addWhiteListURL"
      >
        {{ $t('Create') }}
      </b-button>
    </div>
  </b-modal>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  data() {
    return {
      siteName: "",
      url: "",
      stateSiteName: null,
      stateURL: null,
      groupName: "",
      group_id: 3,
      urlError: "",
    };
  },
  methods: {
    show(groupName) {
      this.clear();
      this.groupName = groupName;
      this.stateSiteName = null;
      this.stateURL = null;
      return this.$refs["bv-modal-whitelist"].show();
    },
    clear() {
      this.siteName = "";
      this.url = "";
      this.urlError = "";
    },
    addWhiteListURL() {
      if (!this.siteName.trim()) {
        this.stateSiteName = false;
        return;
      }
      if (!this.url) {
        this.stateURL = false;
        return;
      }
      // Validate the URL using the regex pattern
      if (!this.validateURL(this.url)) {
        this.stateURL = false;
        this.urlError = this.$t("Please enter a valid URL.");
        return;
      }
      const site = this.siteName.toLocaleLowerCase().trim().replaceAll(" ", "_");
      const data = {
        key: `white_list.${site}`,
        format: "text",
        config: this.url,
        name: this.siteName,
        group: this.groupName,
        group_id: this.group_id,
        hidden: false,
        ui: null,
      };
      ProcessMaker.apiClient
        .post("settings", data)
        .then(() => {
          this.$parent.refresh();
          this.$refs["bv-modal-whitelist"].hide();
        });
    },
  },
};
</script>
