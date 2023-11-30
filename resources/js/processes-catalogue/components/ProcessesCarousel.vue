<template>
  <div>
    <b-carousel
      id="carousel-1"
      class="custom-carousel"
      v-model="slide"
      :interval="interval"
      indicators
      @sliding-start="onSlideStart"
      @sliding-end="onSlideEnd"
    >
      <b-carousel-slide
        v-for="(image, index) in images.length > 0 ? images : defaultImage"
        :key="index"
        :img-src="image.url"
      ></b-carousel-slide>
    </b-carousel>
  </div>
</template>

<script>
export default {
  props: {
    interval: {
      type: Number,
      required: true,
    },
    process: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      slide: 0,
      sliding: null,
      images: [
        { url: "https://picsum.photos/1024/480/?image=55" },
        { url: "https://picsum.photos/800/400/?image=54" },
      ],
      defaultImage: Array(4).fill({ url: "/img/launchpad-images/noImage.svg" }),
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
<style>
.custom-carousel {
  background: #e3e3e3;
  width: 593px;
  height: 394px;
  text-shadow: 1px 1px 2px #333;
  max-height: 394px;
}
</style>
