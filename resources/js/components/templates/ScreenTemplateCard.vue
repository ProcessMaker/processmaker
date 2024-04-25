<template>
  <div>
    <b-card
      no-body
      bg-variant="transparent"
      class="screen-template-card p-0"
      :data-cy="`${template.name}-card`"
    >
      <b-card-body>
        <div @click="selectTemplate" class="template-container">
          <div
            v-if="thumbnail"
            class="thumbnail-container thumbnail-image-container"
            :class="{'active': isActive }"
            :style="{ backgroundImage: 'url(' + thumbnail + ')'}"
          />
          <div
            v-else
            class="thumbnail-container thumbnail-icon-container d-flex align-items-center justify-content-center"
            :class="{'active': isActive }"
          >
            <i class="fas fa-palette thumbnail-icon" />
          </div>
          <div class="template-details">
            <span class="template-name d-block pt-1">{{ template.name | str_limit(30) }}</span>
            <span class="template-description d-block">{{ template.description | str_limit(150) }}</span>
          </div>
        </div>
        <div class="default-template-container d-flex align-items-end">
          <b-form-checkbox
            v-model="isDefaultTemplate"
            name="default-template"
          >
            <span class="checkbox-label">{{ $t('Set as Default Template') }}</span>
          </b-form-checkbox>
        </div>
        <div v-if="!isBlankTemplate" class="preview-template pt-1">
          <b-link @click="showTemplatePreview">
            <i class="fas fa-eye fa-fw mr-0 pr-3 preview-icon" />
            {{ $t('Preview Template') }}
          </b-link>
        </div>
      </b-card-body>
    </b-card>
  </div>
</template>

<script>
import templateMixin from "./mixins/template.js";

export default {
  mixins: [templateMixin],
  props: ["template", "selectedTemplateId", "isActive", "defaultTemplateId", "defaultTemplateScreenType", 'isDefaultTemplatePublic'],
  data() {
    return {
      isDefaultTemplate: false,
    };
  },
  computed: {
    thumbnail() {
      if (this.template?.template_media && this.template.template_media.length > 0) {
        return this.template.template_media[0].url;
      } else if (this.template?.template_media?.thumbnail?.url) {
        return this.template?.template_media.thumbnail.url
      }
      return null;
    },
    isBlankTemplate() {
      return !this.template.hasOwnProperty('uuid') ? true : false;
    }
  },
  watch: {
    template: {
      deep: true,
      handler() {
        this.updateDefaultTemplateStatus();
      }
    },
    isDefaultTemplate(newValue, oldValue) {
      if (newValue) {
        this.emitDefaultTemplateSelected();
      }
    },
    defaultTemplateId(newValue, oldValue) {
      if (newValue === undefined && oldValue === null || newValue === null && oldValue === undefined) {
        return;
      } else if (newValue !== oldValue){
        if (this.template.id === oldValue || (!this.template.hasOwnProperty('id') && oldValue === null)) {
          this.isDefaultTemplate = false;
        } else if (newValue === null && !this.template.hasOwnProperty('id') && this.template.screen_type === this.defaultTemplateScreenType) {
          this.isDefaultTemplate = true;
        }
      } 
    },
    defaultTemplateScreenType() {
      this.updateDefaultTemplateStatus();
    },
    isDefaultTemplatePublic() {
      this.updateDefaultTemplateStatus();
    },
  },
  mounted() {
    this.updateDefaultTemplateStatus();
  },
  methods: {
    showTemplatePreview() {
      this.$emit("show-template-preview", this.template);
    },
    selectTemplate() {
      this.$emit("template-selected", this.template.id);
    },
    updateDefaultTemplateStatus() {
      if ((this.defaultTemplateId === null || this.defaultTemplateId === undefined) && this.template.screen_type == this.defaultTemplateScreenType.toString() && !this.template.hasOwnProperty("id") && this.template.is_public === this.isDefaultTemplatePublic) {
        this.isDefaultTemplate = true;
      } else {
        this.isDefaultTemplate = this.template.screen_type === this.defaultTemplateScreenType.toString() && !!this.template.is_default_template && this.template.is_public === this.isDefaultTemplatePublic;
      }
    },
    emitDefaultTemplateSelected() {
      const defaultTemplateId = this.template?.id || null;
      this.$emit("template-default-selected", defaultTemplateId);
      this.$emit("template-selected", defaultTemplateId);
    },
  },
};
</script>

<style lang="scss" scoped>

.screen-template-card {
  border: none;
}

.card-image {
  border-radius: 6px;
}

.thumbnail-container {
  width: 247px;
  height: 133px;
  border: 2px solid #CDDDEE;
  border-radius: 7px;
  background-color: #fff;
}

.thumbnail-container:hover,
.thumbnail-container.active {
  border-color: #1572C2;
  cursor: pointer;
}

.thumbnail-image-container {
  background-size: contain;
  background-position: center;
  background-repeat: no-repeat;
}

.thumbnail-icon {
  color: #CDDDEE;
  font-size: 59px;
}
.template-details, .checkbox-label {
  color: #556271;
}

.template-name {
  font-size: 14px;
  font-weight: 600;
  line-height: 24px;
}

.template-description, .checkbox-label, .preview-template {
  font-size: 12px;
  font-weight: 400;
  line-height: 18px;
}

.checkbox-label {
  vertical-align: bottom;
  display: inline-block;
  margin-bottom: 1px;
}

.preview-icon {
  font-size: 14px;
  padding-right: 1.25rem;
}

</style>
