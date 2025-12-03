<template>
  <div>
    <b-carousel
      ref="carousel"
      :interval="0"
      class="carousel"
    >
      <div class="controls tw-flex tw-content-stretch tw-absolute tw-w-full tw-z-10 tw-top-1/2 tw-flex-wrap">
        <button
          class="tw-content-center tw-grow tw-left-0 tw-absolute tw-bg-transparent tw-color-[#556271] tw-text-xl"
          @click="prevSlide()"
        >
          <i class="fas fa-caret-left" />
        </button>
        <button
          class="tw-content-center tw-grow tw-right-0 tw-absolute tw-bg-transparent tw-color-[#556271] tw-text-xl"
          @click="nextSlide()"
        >
          <i class="fas fa-caret-right" />
        </button>
      </div>
      <b-carousel-slide
        v-for="(image, index) in images.length > 0 ? images : defaultImage"
        :key="index"
      >
        <template #img>
          <div @click="resizeCarousel(image.url, index)">
            <iframe
              v-if="image.type === 'embed'"
              ref="slides"
              class="content carousel-normal"
              :src="image.url"
              title="embed media"
            />
            <img
              v-else
              ref="slides"
              class="content carousel-normal"
              :src="image.url"
              :alt="process.name"
            >
          </div>
        </template>
      </b-carousel-slide>
    </b-carousel>
  </div>
</template>

<script>
import CarouselMixin from "./mixins/CarouselMixin";

export default {
  mixins: [CarouselMixin],
  props: {
    process: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      fullPage: false,
      images: [],
      imagesLoaded: true,
      defaultImage: Array(4).fill({
        url: "/img/launchpad-images/defaultImage.svg",
      }),
    };
  },
  mounted() {
    this.getLaunchpadImages();
    ProcessMaker.EventBus.$on(
      "getLaunchpadImagesEvent",
      ({ indexImage, type }) => {
        if (type === "delete") {
          this.images.splice(indexImage, 1);
        } else {
          this.images = [];
          this.getLaunchpadImages();
        }
      },
    );
  },
  methods: {
    prevSlide() {
      this.$refs.carousel.prev();
    },
    nextSlide() {
      this.$refs.carousel.next();
    },
    resizeCarousel(url, index) {
      this.fullPage = !this.fullPage;
      this.$emit("full-carousel", {
        url,
        index,
      });
    },
  },
};
</script>

<style scoped>
.controls {
  display: none;
}
.carousel:hover .controls {
  display: block;
}
.carousel-normal {
  width: 100%;
  height: auto;
  aspect-ratio: 16/9;
  object-fit: contain;
  border-radius: 16px;
}
.carousel-full {
  width: 100%;
  height: auto;
  aspect-ratio: 16/9;
  object-fit: contain;
  border-radius: 16px;
  margin-top: 2%;
  margin-bottom: 2%;
}
</style>
