<template>
  <div>
    <b-form-group
      :label="$t('Asset Type')"
      :description="$t('Optional. If set, the value is the ID of the selected asset and it will be exported with scripts that use this variable.')"
      :invalid-feedback="errorMessage('asset_type', errors)"
      :state="errorState('asset_type', errors)"
    >
      <b-form-select
        v-model="localAssetType"
        :options="assetTypeOptions"
        :state="errorState('asset_type', errors)"
        name="asset_type"
        @change="onAssetTypeChange"
      />
    </b-form-group>

    <b-form-group
      v-if="localAssetType"
      :label="$t('Asset')"
      :invalid-feedback="errorMessage('value', errors)"
      :state="errorState('value', errors)"
    >
      <multiselect
        v-model="selectedAsset"
        :options="assetOptions"
        :loading="isLoadingAssets"
        :placeholder="$t('Type to search')"
        :show-labels="false"
        :internal-search="false"
        :options-limit="20"
        track-by="id"
        :label="nameField"
        @search-change="loadAssets"
        @input="onAssetSelected"
      />
    </b-form-group>

    <b-form-group
      v-if="!localAssetType"
      :label="$t('Value')"
      :invalid-feedback="errorMessage('value', errors)"
      :state="errorState('value', errors)"
    >
      <b-form-textarea
        v-model="localValue"
        autocomplete="off"
        rows="10"
        :state="errorState('value', errors)"
        name="value"
        @input="emitValue"
      />
      <small v-if="valueHint" class="form-text text-muted">{{ valueHint }}</small>
    </b-form-group>

    <b-form-group
      v-else
      :label="$t('Value')"
      :invalid-feedback="errorMessage('value', errors)"
      :state="errorState('value', errors)"
    >
      <b-form-input
        :value="derivedValueLabel"
        plaintext
        readonly
      />
      <small class="form-text text-muted">
        {{ $t('Value is set automatically to the selected asset ID.') }}
      </small>
    </b-form-group>
  </div>
</template>

<script>
import { Multiselect } from "@processmaker/vue-multiselect";
import { FormErrorsMixin } from "SharedComponents";
import assetTypes from "../assetTypes";

export default {
  components: { Multiselect },
  mixins: [FormErrorsMixin],
  props: {
    assetType: {
      type: String,
      default: null,
    },
    value: {
      type: [String, Number],
      default: "",
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
    valueHint: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      localAssetType: this.assetType || "",
      localValue: this.value || "",
      selectedAsset: null,
      assetOptions: [],
      isLoadingAssets: false,
      assetTypes,
    };
  },
  computed: {
    assetTypeOptions() {
      return [
        { value: "", text: this.$t("None") },
        ...this.assetTypes.map((type) => ({
          value: type.class,
          text: this.$t(type.label),
        })),
      ];
    },
    selectedTypeConfig() {
      return this.assetTypes.find((type) => type.class === this.localAssetType) || null;
    },
    nameField() {
      return this.selectedTypeConfig?.nameField || "name";
    },
    derivedValueLabel() {
      if (this.selectedAsset) {
        const name = this.selectedAsset[this.nameField] || this.selectedAsset.name || "";
        return `${this.selectedAsset.id}${name ? ` (${name})` : ""}`;
      }
      return this.$t("Select an asset");
    },
  },
  watch: {
    assetType(next) {
      this.localAssetType = next || "";
      if (this.localAssetType && this.value) {
        this.loadSelectedAsset();
      }
    },
    value(next) {
      if (this.localAssetType && next) {
        this.loadSelectedAsset();
      }
    },
  },
  mounted() {
    if (this.localAssetType && this.value) {
      this.loadSelectedAsset();
    } else if (this.localAssetType) {
      this.loadAssets("");
    }
  },
  methods: {
    onAssetTypeChange() {
      this.selectedAsset = null;
      this.assetOptions = [];
      this.$emit("update:assetType", this.localAssetType || null);
      this.$emit("update:value", this.localAssetType ? "" : this.localValue);
      if (this.localAssetType) {
        this.loadAssets("");
      }
    },
    onAssetSelected(asset) {
      this.$emit("update:assetType", this.localAssetType || null);
      this.$emit("update:value", asset ? String(asset.id) : "");
    },
    emitValue() {
      this.$emit("update:value", this.localValue);
    },
    loadAssets(query) {
      if (!this.selectedTypeConfig) {
        return;
      }
      this.isLoadingAssets = true;
      ProcessMaker.apiClient
        .get(this.selectedTypeConfig.apiPath, {
          params: {
            filter: query || "",
            per_page: 20,
            order_by: this.nameField,
            order_direction: "asc",
          },
        })
        .then((response) => {
          this.assetOptions = response.data.data || [];
        })
        .catch(() => {
          this.assetOptions = [];
        })
        .finally(() => {
          this.isLoadingAssets = false;
        });
    },
    loadSelectedAsset() {
      if (!this.selectedTypeConfig || !this.value) {
        return;
      }
      const assetId = String(this.value);
      this.isLoadingAssets = true;
      ProcessMaker.apiClient
        .get(this.selectedTypeConfig.apiPath, {
          params: {
            filter: "",
            per_page: 50,
          },
        })
        .then((response) => {
          const items = response.data.data || [];
          this.assetOptions = items;
          this.selectedAsset = items.find((item) => String(item.id) === assetId) || null;
          if (!this.selectedAsset) {
            this.selectedAsset = {
              id: assetId,
              [this.nameField]: assetId,
            };
            this.assetOptions = [this.selectedAsset, ...items];
          }
        })
        .catch(() => {
          this.selectedAsset = {
            id: assetId,
            [this.nameField]: assetId,
          };
          this.assetOptions = [this.selectedAsset];
        })
        .finally(() => {
          this.isLoadingAssets = false;
        });
    },
  },
};
</script>
