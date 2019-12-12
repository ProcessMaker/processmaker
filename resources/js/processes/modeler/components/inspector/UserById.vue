<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>

        <b-form-input v-model="content"></b-form-input>

        <small v-if="error" class="text-danger">{{ error }}</small>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    </div>
</template>

<script>
  export default {
    props: ["value", "label", "helper"],
    data() {
      return {
        content: '', 
        error: ''
      };
    },
    watch: {
      value: {
          handler() {
            this.content = this.value;
          },
          immediate: true,
      },
      content: {
        handler() {
          this.$emit("input", this.content);
        }
      },
    },
    methods: {
    }
  };
</script>
