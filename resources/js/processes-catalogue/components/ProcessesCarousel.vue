<template>
  <div>
    <b-carousel
      id="carousel-1"
      v-model="slide"
      :interval="interval"
      indicators
      img-height="400px"
      @sliding-start="onSlideStart"
      @sliding-end="onSlideEnd"
    >
      <b-carousel-slide
        v-for="(image, index) in images.length > 0 ? images : defaultImage"
        :key="index"
        class="custom-style"
        :img-src="image.url"
      ></b-carousel-slide>
    </b-carousel>
  </div>
</template>

<script>
export default {
  props: {
    process: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      slide: 0,
      sliding: null,
      images: [],
      defaultImage: Array(4).fill({ url: "/img/launchpad-images/defaultImage.svg" }),
      interval: 2000,
    };
  },
  methods: {
    onSlideStart(slide) {
      this.sliding = true;
    },
    onSlideEnd(slide) {
      this.sliding = false;
    },
    /**
     * TO DO: This method gets information related to processes Launchpad Settings
     */
    getLaunchpadSettings() {
      //Here call API to retrieve information
      // ProcessMaker.apiClient
      //   .get("query here")
      //   .then((response) => {
      //     this.carouselImages = response.data;
      //   })
      //   .catch((err) => {
      //     console.log("Error", err);
      //   });
    },
  },
};
</script>
<style scoped>
.carousel-inner {
  overflow: hidden;
}

.custom-style {
  background-size: cover;
  background-position: center;
  width: 100%;
  height: 400px;
}
</style>
