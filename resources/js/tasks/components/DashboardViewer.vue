<template>
    <div>
        <vue-form-renderer
          v-model="formData"
          :config="screen.config"
          :computed="screen.computed"
          :custom-css="screen.custom_css"
          :watchers="screen.watchers"
        />
    </div>
</template>

<script>

  export default {
    props: ["screen", "formData"],
    data() {
      return {
        disabled: false,
      };
    },
    methods: {},
    watch: {
      screen: {
        deep: true,
        handler() {
          this.disabled = false;
        }
      }
    }
  };
</script>
