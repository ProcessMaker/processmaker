<template>
  <multiselect
    id="screenTypeDropdown"
    v-model="selectedType"
    :options="screenTypeOptions"
    track-by="type"
    label="type"
    aria-label="type"
    :allow-empty="false"
    :searchable="false"
    :multiple="false"
    :option-height="100"
    :show-labels="false"
    :prevent-autofocus="true"
    :disabled="isDisabled"
    class="screen-type-select mt-2"
    @input="emitSelectedType"
  >
    <template slot="singleLabel" slot-scope="props">
      <div class="type-container" :data-cy="`type-container-${props.option.typeHuman}`">
        <i class="type-icon-placeholder pr-3" :class="props.option.icon" />
        <span class="type-desc">
          <span class="type-title-placeholder">{{ props.option.typeHuman }}</span>
          <span v-if="!hideDescription" class="type-desc-placeholder">{{ props.option.description }}</span>
        </span>
      </div>
    </template>
    <template slot="option" slot-scope="props">
      <div class="type-container" :data-cy="`type-container-${props.option.typeHuman}`">
        <i class="type-icon p-3" :class="props.option.icon" />
        <span class="type-desc">
          <span class="type-title-option">{{ props.option.typeHuman }}</span>
          <span v-if="!hideDescription" class="type-desc-option">{{ props.option.description }}</span>
        </span>
      </div>
    </template>
  </multiselect>
</template>

<script>

export default {
  props: ["value", "copyAssetMode", "screenTypes", "hideDescription"],
  data() {
    return {
      isDisabled: false,
      selectedType: {
        type: "FORM",
        typeHuman: "Form",
        icon: "fas fa-file",
        description: "Design interactive and complex multi-page forms.",
      },
    };
  },
  computed: {
    screenTypeOptions() {
      // Check if this.screenTypes is an array or an object and map accordingly
      let optionsArray;
      if (Array.isArray(this.screenTypes)) {
        optionsArray = this.screenTypes.map(screenType => this.createScreenTypeOption(screenType));
      } else if (typeof this.screenTypes === 'object' && this.screenTypes !== null) {
        optionsArray = Object.entries(this.screenTypes).map(([key, value]) => this.createScreenTypeOption(key));
      }

      return this.sortOptions(optionsArray);
    },
  },
  mounted() {
    if (this.copyAssetMode) {
      this.isDisabled = true;
    }

    // Find the matching value in screenTypeOptions or default to FORM
    if (this.value) {
      this.selectedType = this.screenTypeOptions.find(item => item.type === this.value);
    }
    this.$emit("input", this.selectedType.type);
  },
  methods: {
    emitSelectedType() {
      this.$emit("input", this.selectedType.type);
    },
    createScreenTypeOption(screenType) {
      let type;
      let typeHuman;
      let icon;
      let description;

      switch (screenType) {
        case "FORM":
          type = "FORM";
          typeHuman = "Form";
          icon = "fas fa-file";
          description = this.$t("Design interactive and complex multi-page forms.");
          break;
        case "EMAIL":
          type = "EMAIL";
          typeHuman = "E-mail";
          icon = "fas fa-envelope";
          description = this.$t("Compose the email body for email messages.");
          break;
        case "DISPLAY":
          type = "DISPLAY";
          typeHuman = "Display";
          icon = "fas fa-desktop";
          description = this.$t("Display information or allow Request participants to download files.");
          break;
        case "CONVERSATIONAL":
          type = "CONVERSATIONAL";
          typeHuman = "Conversational";
          icon = "fas fa-comment";
          description = this.$t("Design functional rule-based modern chat style experiences.");
          break;

        default:
          type = "FORM";
          typeHuman = this.$t("Form");
          icon = "fas fa-file";
          description = this.$t("Design interactive and complex multi-page forms.");
          break;
      }

      return {
        type,
        typeHuman,
        icon,
        description,
      };
    },
    sortOptions(optionsArray) {
      const order = ["FORM", "EMAIL", "DISPLAY", "CONVERSATIONAL"];
      return optionsArray.sort((a, b) => order.indexOf(a.type) - order.indexOf(b.type));
    },
  },
};

</script>

<style lang="scss" scoped>
  @import '../../../../sass/colors';

  .type-container {
    display: flex;
    align-items: center;
  }

  .type-icon-placeholder {
    color: $primary;
    border-color: $primary;
    font-size: 32px;
  }

  .type-icon {
    color: $primary;
    border-color: $primary;
    font-size: 24px;
  }

  .type-desc {
    display: flex;
    flex-direction: column;
  }

  .type-title-option {
    font-size: 14px;
  }

  .type-desc-placeholder {
    font-size: 14px;
  }

  .type-desc-option {
    font-size: 12px;
  }

</style>
