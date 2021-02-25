<template>
  <span class="setting-text">
    <b-input-group>
      <b-form-textarea v-model="input" rows="6" class="border-right-0" spellcheck="false"></b-form-textarea>
      <b-input-group-append>
        <b-button variant="primary" :disabled="disabled" @click="onSave">
          <i class="fa fa-save"></i>
        </b-button>
      </b-input-group-append>
    </b-input-group>
  </span>
</template>

<script>
export default {
  props: ['value'],
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
    onSave() {
      this.$emit('change', this.input);
      this.$emit('input', this.input);
      this.original = this.input;
      this.disabled = true;
    }
  },
  mounted() {
    if (typeof this.value == 'object' || typeof this.value == 'array') {
      this.input = JSON.stringify(this.value, null, 2);
    } else {
      this.input = this.value;
    }

    if (this.input == "null" || this.input === null) {
      this.input = '';
    }

    this.original = this.input;
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