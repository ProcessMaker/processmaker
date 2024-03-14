<template>
  <div>
    <b-card
      no-body
      bg-variant="transparent"
      class="screen-template-card p-0"
      data-cy="screen-template-card"
    >
      <b-card-body>
        <div @click="selectTemplate" class="template-container">
          <div v-if="thumbnail" class="thumbnail-container thumbnail-image-container" :class="{'active': isActive }" :style="{ backgroundImage: 'url(' + thumbnail + ')'}"></div>
          <div v-else class="thumbnail-container thumbnail-icon-container d-flex align-items-center justify-content-center" :class="{'active': isActive }">
            <i class="fas fa-palette thumbnail-icon"></i>
          </div>
          <div class="template-details">
            <span class="template-name d-block pt-1">{{ template.name | str_limit(30) }}</span>
            <span class="template-description d-block pb-1">{{ template.description | str_limit(150) }}</span>
          </div>
        </div>
        <div class="default-template d-flex align-items-end">
          <b-form-checkbox
            v-model="defaultTemplate"
            name="default-template"
          >
            <span class="checkbox-label">{{ $t('Set as Default Template') }}</span>
          </b-form-checkbox>
        </div>
        <div class="preview-template pt-1">
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
  props: ["template", 'selectedTemplateId', "isActive"],
  data() {
    return {
      defaultTemplate: null,
    };
  },
  watch: {
  },
  computed: {
    thumbnail() {
      return this.template?.thumbnails && this.template.thumbnails.length > 0 ? this.template.thumbnails[0] : null;
    },
  },
  methods: {
    showTemplatePreview() {
      this.$emit('show-template-preview', this.template);
    },
    selectTemplate() {
      this.$emit('template-selected', this.template.id);
    }
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
.template-details, .default-template {
  color: #556271;
}

.template-name {
  font-size: 14px;
  font-weight: 600;
  line-height: 24px;
}

.template-description, .default-template, .preview-template {
  font-size: 12px;
  font-weight: 400;
  line-height: 18px;
}

.checkbox-label {
  vertical-align: bottom;
  display: inline-block;
  margin-bottom: -3px;
}

.preview-icon {
  font-size: 14px;
  padding-right: 1.25rem;
}

</style>
