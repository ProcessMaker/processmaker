<template>
  <multiselect
    v-model="selectedTemplateType"
    :options="templateTypes"
    track-by="type"
    label="type"
    aria-label="type"
    :allow-empty="false"
    :searchable="false"
    :multiple="false"
    :show-labels="false"
    :prevent-autofocus="true"
    class="template-type-select mt-2"
    @input="emitSelectedTemplate"
  >
    <template slot="singleLabel" slot-scope="props">
      <div class="type-container" :data-cy="`type-container-${props.option.type}`">
        <TemplateIcon class="template-type-icon" />
        <span class="type-desc">
          <span class="template-type-placeholder">{{ props.option.type }}</span>
        </span>
      </div>
    </template>
    <template slot="option" slot-scope="props">
      <div class="type-container" :data-cy="`type-container-${props.option.type}`">
        <span class="type-desc">
          <span class="type-title-option">{{ props.option.type }}</span>
        </span>
      </div>
    </template>
  </multiselect>
</template>

<script>
import TemplateIcon from "./TemplateIcon.vue";

export default {
  components: {
    TemplateIcon,
  },
  data() {
    return {
      selectedTemplateType: [
        {
          type: "Shared Templates",
        },
      ],
      templateTypes: [
        {
          type: "Shared Templates",
        },
        {
          type: "My Templates",
        },
      ],
    };
  },
  mounted() {
    this.selectedTemplateType.type = "Shared Templates";
    this.$emit("selected-template", this.selectedTemplateType.type);
  },
  methods: {
    emitSelectedTemplate() {
      this.$emit("selected-template", this.selectedTemplateType.type);
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

    .template-type-icon {
      margin-left: 4px;
      margin-right: 15px;
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
