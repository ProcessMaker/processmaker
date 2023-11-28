<template>
  <div>
    <modal
      id="wizardTemplateDetails"
      size="huge"
      :hide-footer="true"
      @close="close"
    >
      <div class="wizard-details-container text-left mb-3">
        <img :src="template.icon" :alt="template.name + 'icon'" width="45px" class="mb-3">
        <h5 class="text-uppercase mb-1">{{ template.name | str_limit(30) }}</h5>
        <h2>{{ template.headline | str_limit(150) }}</h2>
        <h4>{{ template.description | str_limit(150) }}</h4>
      </div>
      <b-carousel-slide
        :img-src="template.sliderImages"
      ></b-carousel-slide>
    </modal>
  </div>
</template>

<script>
  export default {
    components: { },
    mixins: [],
    props: ["template"],
    data: function() {
      return {
      }
    },
    methods: {
    },
    computed: {
    },
    mounted() {
    },
  };
</script>

<style scoped>

</style>