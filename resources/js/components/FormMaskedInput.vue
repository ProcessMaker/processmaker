<template>
  <div class="form-group">
    <a v-if="hasAISuggestion" href="#suggestion" class="ai-suggest" @click.prevent="suggestInput">
      <i class="fas fa-robot"></i>
    </a>
    <required-asterisk /><label v-uni-for="name">{{ label }}</label>
    <component
      :is="componentType"
      v-if="componentType !== 'input'"
      v-model="localValue"
      v-bind="componentConfigComputed"
      v-uni-id="name"
      :name="name"
      class="form-control"
      :class="classList"
      type="text"
      @change="onChange"
    />
    <input
      v-else
      v-model="localValue"
      v-bind="componentConfig"
      v-uni-id="name"
      :name="name"
      class="form-control"
      :class="classList"
      :type="dataType"
      :maxlength="maxlength"
      @change="onChange"
    />
    <template v-if="validator && validator.errorCount">
      <div
        v-for="(errors, index) in validator.errors.all()"
        :key="index"
        class="invalid-feedback"
      >
        <div v-for="(error, subIndex) in errors" :key="subIndex">
          {{ error }}
        </div>
      </div>
    </template>
    <div v-if="error" class="invalid-feedback">{{ error }}</div>
    <small v-if="helper" class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>
import { FormMaskedInput } from '@processmaker/screen-builder';

export default {
  extends: FormMaskedInput,
  computed: {
    hasAISuggestion() {
      return this.config.aiSuggestion;
    },
  },
  methods: {
    async suggestInput() {
      const session = window.ProcessMaker.ai.createTextSession();
      const prompt = this.config.aiSuggestion;
      const response = await session.prompt(prompt, this.transientData);
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
