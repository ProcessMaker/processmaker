<template>
  <div class="options-row">
    {{value}}
    <PMFloatingButtons ref="PMFloatingButtons1"
                       :buttonEdit="buttonEdit"
                       :buttonRemove="buttonRemove">
    </PMFloatingButtons>
  </div>
</template>

<script>
  import PMFloatingButtons from "../../components/PMFloatingButtons.vue";
  export default {
    components: {
      PMFloatingButtons
    },
    props: {
      value: null,
      name: null,
      row: null,
      buttonEdit: null,
      buttonRemove: null
    },
    mounted() {
      this.row[this.name].target = this;
    },
    data() {
      return {
        show() {
          this.$refs.PMFloatingButtons1.show();
        },
        close() {
          this.$refs.PMFloatingButtons1.close();
        }
      };
    },
    methods: {
    }
  };
</script>

<style scoped>
  .options-row{
    min-width: 100px;
  }
</style>

