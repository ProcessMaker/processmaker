<template>
  <b-modal
    id="bv-modal-whitelist"
    ref="bv-modal-whitelist"
    hide-footer
  >
    <template #modal-title>
      <div class="tw-self-stretch tw-text-base tw-font-medium tw-text-[#20242A]">
        {{ $t("URL Creation") }}
      </div>
      <div class="tw-self-stretch tw-text-sm tw-text-[#596372]">
        {{ $t("Please provide your information to create an account.") }}
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
            {{ $t("Site Name") }}
          </label>
          <b-form-input
            id="url"
            v-model="url"
            type="text"
            required
            placeholder="http://www.sample.org/head"
            :state="stateURL"
          />
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
export default {
  data() {
    return {
      siteName: "",
      url: "",
      stateSiteName: null,
      stateURL: null,
      groupName: "",
      group_id: 3,
    };
  },
  methods: {
    show(groupName) {
      this.clear();
      this.groupName = groupName;
      return this.$refs["bv-modal-whitelist"].show();
    },
    clear() {
      this.siteName = "";
      this.url = "";
    },
    addWhiteListURL() {
      if (!this.siteName) {
        this.stateSiteName = false;
        return;
      }
      if (!this.url) {
        this.stateURL = false;
        return;
      }
      const site = this.siteName.toLocaleLowerCase().trim().replaceAll(" ", "_");
      const data = {
        key: `whiteList.${site}`,
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
