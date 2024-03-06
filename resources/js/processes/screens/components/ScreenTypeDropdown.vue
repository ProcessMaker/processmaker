<template>
  <multiselect
    v-model="selectedType"
    :options="screenTypes"
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
      <div class="type-container">
        <i class="type-icon-placeholder pr-3" :class="props.option.icon" />
        <span class="type-desc">
          <span class="type-title-placeholder">{{ props.option.type }}</span>
          <span class="type-desc-placeholder">{{ props.option.description }}</span>
        </span>
      </div>
    </template>
    <template slot="option" slot-scope="props">
      <div class="type-container">
        <i class="type-icon p-3" :class="props.option.icon" />
        <span class="type-desc">
          <span class="type-title-option">{{ props.option.type }}</span>
          <span class="type-desc-option">{{ props.option.description }}</span>
        </span>
      </div>
    </template>
  </multiselect>
</template>

<script>

export default {
  props: ["copyAssetMode"],
  data() {
    return {
      isDisabled: false,
      selectedType: {
        type: "Form",
        icon: "fas fa-file",
        description: "Fill the form and the information will be copied to the other forms and saved as a draft.",
      },
      screenTypes: [
        {
          type: "Form",
          icon: "fas fa-file",
          description: "Fill the form and the information will be copied to the other forms and saved as a draft.",
        },
        {
          type: "E-Mail",
          icon: "fas fa-envelope",
          description: "Fill the form and the information will be copied to the other forms and saved as a draft.",
        },
        {
          type: "Display",
          icon: "fas fa-desktop",
          description: "Fill the form and the information will be copied to the other forms and saved as a draft.",
        },
        {
          type: "Conversational",
          icon: "fas fa-comment",
          description: "Fill the form and the information will be copied to the other forms and saved as a draft.",
        },
      ],
    };
  },
  mounted() {
    if (this.copyAssetMode) {
      this.isDisabled = true;
    }

    this.selectedType.type = "Form";
    this.$emit("input", this.selectedType.type);
  },
  methods: {
    emitSelectedType() {
      this.$emit("input", this.selectedType.type);
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
