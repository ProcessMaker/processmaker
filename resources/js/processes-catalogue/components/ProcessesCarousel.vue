<template>
  <div class="d-block">
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
  mounted() {
    this.getLaunchpadImages();
  },
  methods: {
    onSlideStart(slide) {
      this.sliding = true;
    },
    onSlideEnd(slide) {
      this.sliding = false;
    },
    /**
     * Get images from Media library related to process
     */
    getLaunchpadImages() {
      ProcessMaker.apiClient
        .get("processes/"+this.process.id+"/media")
        .then(response => {
            console.log("getLaunchpadImages:", response.data.data);
            const mediaArray = response.data.data[0].media;
            mediaArray.forEach((media) => {
            this.images.push({ url: media.original_url });
          });
        });
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
