<template>
  <div>
    <modal
      id="wizardTemplateDetails"
      class="wizard-template-modal"
      size="huge"
      :hide-footer="true"
      @close="close"
    >
      <b-row>
        <b-col>
          <div class="wizard-details-container text-left mb-3">
            <span>
              <img
                :src="template.icon"
                :alt="template.name + 'icon'"
                width="45px"
                class="mb-3 d-inline-block"
              >
            </span>
            <span>
              <h5 class="text-uppercase mb-1 d-inline-block font-weight-bold template-name">{{ template.name | str_limit(30) }}</h5>
            </span>
            <div class="wizard-details-text">
              <h2 class="wizard-details-headline">{{ template.headline | str_limit(150) }}</h2>
              <p class="wizard-details-description">{{ template.description | str_limit(150) }}</p>
              <button class="wizard-details-button text-uppercase">
                <i class="fas fa-play-circle" />
                {{ $t('Call to Action') }}
              </button>
            </div>
          </div>
        </b-col>
        <b-col>
          <b-carousel :interval="0">
            <b-carousel-slide v-for="(image, index) in template.sliderImages" :key="index" :img-src="image"/>
          </b-carousel>
        </b-col>
      </b-row>
    </modal>
  </div>
</template>

<script>
import Modal from "../shared/Modal.vue";

export default {
  components: { Modal },
  mixins: [],
  props: ["template"],
  data() {
    return {
    };
  },
  computed: {
  },
  methods: {
    show() {
      this.$bvModal.show("wizardTemplateDetails");
    },
    close() {
      this.$bvModal.hide("wizardTemplateDetails");
    },
  },
};
</script>

<style scoped>
.wizard-details-text {
  font-weight: 400;
}
.wizard-details-description {
  font-size: 16px;
}

.wizard-details-headline {
  font-size: 26px;
}

.wizard-details-button{
  border-radius: 11px;
  border: none;
  background-color: #1572C2;
  color: #FFFFFF;
  display: inline-flex;
  padding: 7px 16px;
  align-items: center;
}
</style>
