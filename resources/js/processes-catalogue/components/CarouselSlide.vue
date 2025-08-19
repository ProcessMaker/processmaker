<template>
  <div>
    <b-carousel
      ref="carousel"
      :interval="0"
      controls
    >
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
