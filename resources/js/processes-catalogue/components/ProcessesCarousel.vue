<template>
  <div>
    <b-carousel
      id="carousel-1"
      v-model="slide"
      :interval="2000"
      indicators
      background="#E3E3E3"
      img-width="593"
      img-height="394"
      style="text-shadow: 1px 1px 2px #333"
      @sliding-start="onSlideStart"
      @sliding-end="onSlideEnd"
    >
      <div v-if="images.length > 0">
        <b-carousel-slide
          v-for="(image, index) in images"
          :key="index"
          :img-src="image.url"
        ></b-carousel-slide>
      </div>
      <div v-else>
        <b-carousel-slide
          v-for="index in 4"
          :key="index"
          :img-src="defaultImage[0].url"
        >
        </b-carousel-slide>
      </div>
    </b-carousel>
  </div>
</template>

<script>
export default {
  props: {
    images: {
      type: Array,
      required: true,
    },
    defaultImage: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      slide: 0,
      sliding: null,
    };
  },
  methods: {
    onSlideStart(slide) {
      this.sliding = true;
    },
    onSlideEnd(slide) {
      this.sliding = false;
    },
  },
};
</script>
