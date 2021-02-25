<template>
  <span class="setting-text">
    <b-input-group>
      <b-form-input ref="input" v-model="input" :readonly="readonly" @keyup.enter="onSave" class="border-right-0" spellcheck="false" autocomplete="off"></b-form-input>
      <b-input-group-append>
        <b-button v-if="!readonly" variant="primary" :disabled="disabled" @click="onSave">
          <i class="fa fa-save"></i>
        </b-button>
        <b-button v-else variant="secondary" @click="onCopy">
          <i class="fas fa-copy"></i>
        </b-button>
      </b-input-group-append>
    </b-input-group>
  </span>
</template>

<script>
export default {
  props: ['value', 'readonly'],
  data() {
    return {
      disabled: true,
      firstRun: true,
      input: null,
      original: null,
    };
  },
  computed: {
    variant() {
      if (this.disabled) {
        return 'secondary';
      } else {
        return 'success';
      }
    },
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    },
    input: {
      handler: function(value) {
        if (!this.firstRun) {
          if (this.original !== value) {
            this.disabled = false;
          } else {
            this.disabled = true;
          }
        }
        this.firstRun = false;
      }
    }
  },
  methods: {
    onCopy() {
      this.$refs.input.select();
      document.execCommand('copy');
    },
    onSave() {
      this.$emit('change', this.input);
      this.$emit('input', this.input);
      this.original = this.input;
      this.disabled = true;
    },
  },
  mounted() {
    if (this.value === null) {
      this.input = '';
      this.original = '';
    } else {
      this.input = this.value;
      this.original = this.value;
    }
  }
};
</script>

<style lang="scss" scoped>
  @import '../../../../sass/colors';
  
  $disabledBackground: lighten($secondary, 20%);
  
  .btn:disabled,
  .btn.disabled {
    background: $disabledBackground;
    border-color: $disabledBackground;
    opacity: 1 !important;
  }
</style>