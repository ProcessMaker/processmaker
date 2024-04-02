<template>
  <div class="carousel-container">
    <b-carousel
      id="processes-carousel"
      v-model="slide"
      no-animation
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
      >
        <template #img>
          <iframe
            v-if="image.type === 'embed'"
            class="d-block iframe-carousel"
            :src="image.url"
            title="embed media"
          />
          <img
            v-else
            :src="image.url"
            :alt="process.name"
            class="d-block img-carousel"
          >
        </template>
      </b-carousel-slide>
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
      interval: 0,
    };
  },
  mounted() {
    this.getLaunchpadImages();
    ProcessMaker.EventBus.$on("getLaunchpadImagesEvent", ({ indexImage, type }) => {
      if (type === "delete") {
        this.images.splice(indexImage, 1);
      } else {
        this.images = [];
        this.getLaunchpadImages();
      }
    });
  },
  methods: {
    onSlideStart(slide) {
      this.sliding = true;
    },
    onSlideEnd(slide) {
      this.sliding = false;
    },
    /**
     * Get images from Media library related to process.
     */
    getLaunchpadImages() {
      ProcessMaker.apiClient
        .get(`processes/${this.process.id}/media`)
        .then((response) => {
          const firstResponse = response.data.data.shift();
          const mediaArray = firstResponse.media;
          const embedArray = firstResponse.embed;
          mediaArray.forEach((media) => {
            const mediaType = media.custom_properties.type ?? 'image';
            this.images.push({
              url: media.original_url,
              type: mediaType
            });
          });
          embedArray.forEach((embed) => {
            let customProperties = JSON.parse(embed.custom_properties)
            this.images.push({ 
              url: customProperties.url,
              type: customProperties.type
            });
          });
        })
        .catch((error) => {
          console.error(error);
        });
    },
  },
};
</script>
<style lang="scss" scoped>
#processes-carousel {
  .carousel-indicators {
    li {
      background-color: #EDEDED;
      width: 27px;
      height: 8px;
      border-radius: 5px;
      border-top: 0;
      border-bottom: 0;
      opacity: 0.5;
    }

    .active {
      background-color: #9C9C9C;
      opacity: 1;
    }
  }
}

.carousel-inner {
  overflow: hidden;
}

.custom-style {
  background-size: cover;
  background-position: center;
  width: 100%;
  height: 400px;
}
.img-carousel {
  max-width: 800px;
  height: 400px;
}
.iframe-carousel {
  border: 0px;
  width: 800px;
  height: 400px;
}
.carousel-container {
  display: flex;
  justify-content: center;
  background-color: #edf1f6;
}
</style>
