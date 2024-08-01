<template>
  <div class="form-group">
    <a v-if="hasAISuggestion" href="#suggestion" class="ai-suggest" @click.prevent="suggestInput">
      <i class="fas fa-robot"></i>
    </a>
    <required-asterisk />
    <label v-uni-for="label">{{ label }}</label>
    <div v-if="richtext" :class="classList" v-uni-id="label">
      <div v-if="readonly" v-html="value"></div>
      <div v-else>
        <editor
          v-if="!readonly && editorActive"
          class="editor"
          v-bind="objectOfAttrs"
          :value="value"
          :init="editorSettings"
          :name="name"
          @input="$emit('input', $event)"
        />
      </div>
    </div>
    <textarea
      v-else
      v-uni-id="label"
      v-bind="objectOfAttrs"
      class="form-control"
      :rows="rows"
      :readonly="readonly"
      :class="classList"
      :name="name"
      :value="value"
      @input="$emit('input', $event.target.value)"
    />
    <display-errors
      v-if="error || (validator && validator.errorCount)"
      :name="name"
      :error="error"
      :validator="validator"
    />
    <small v-if="helper" class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>
import { FormTextArea } from '@processmaker/vue-form-elements';

export default {
  extends: FormTextArea,
  computed: {
    hasAISuggestion() {
      return this.config.aiSuggestion;
    },
  },
  methods: {
    async suggestInput() {
      const session = window.ProcessMaker.ai.createTextSession();
      const prompt = this.config.aiSuggestion;
      const response = await session.prompt(prompt, this.validationData);
      this.$emit('input', response);
    },
  },
}
</script>

<style scoped>
.ai-suggest {
  position: absolute;
  right: 1rem;
}
</style>
