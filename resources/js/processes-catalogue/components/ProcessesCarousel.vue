<template>
  <div
    v-show="imagesLoaded"
    class="h-100 w-100"
  >
    <button
      class="prev"
      @click="prevSlide"
    >
      <i class="fas fa-caret-left"></i>
    </button>
    <button
      class="next"
      @click="nextSlide"
    >
      <i class="fas fa-caret-right"></i>
    </button>

    <div
      ref="containercarousel"
      @resize="updateSlideWidth"
      class="carousel"
      :style="{
        '--var-sizes-sm': `${100 / sizes.sm}%`,
        '--var-sizes-md': `${100 / sizes.md}%`,
        '--var-sizes-lg': `${100 / sizes.lg}%`,
        '--var-sizes-xl': `${100 / sizes.xl}%`,
        '--var-sizes-2xl': `${100 / sizes['2xl']}%`,
        '--var-sizes-3xl': `${100 / sizes['3xl']}%`,
      }"
    >
      <div
        class="slides"
        :style="{ transform: 'translateX(' + translateX + 'px)' }"
      >
        <div
          class="slide"
          v-for="(image, index) in images.length > 0 ? images : defaultImage"
          :key="index"
        >
          <iframe
            v-if="image.type === 'embed'"
            ref="slides"
            class="content d-block iframe-carousel"
            :src="image.url"
            title="embed media"
          />
          <img
            v-else
            ref="slides"
            class="content img-carousel"
            :src="image.url"
            :alt="process.name"
          />
        </div>
      </div>
    </div>
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
      imagesLoaded: true,
      defaultImage: Array(4).fill({
        url: "/img/launchpad-images/defaultImage.svg",
      }),
      interval: 0,
      resizeObserver: null,
      currentIndex: 0,
      slideWidth: 0,
      translateX: 0,
      sizes: {
        sm: 1,
        md: 1,
        lg: 2,
        xl: 2,
        "2xl": 2,
        "3xl": 3,
      },
    };
  },
  computed: {
    slideCount() {
      return this.images.length - 1;
    },
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
      }
    );
    this.$nextTick(() => {
      this.updateSlideWidth();
      this.resizeObserver = new ResizeObserver(this.updateSlideWidth);
      if (this.$refs.containercarousel) {
        this.resizeObserver.observe(this.$refs.containercarousel);
      }
    });
  },
  beforeDestroy() {
    if (this.resizeObserver && this.$refs.containercarousel) {
      this.resizeObserver.unobserve(this.$refs.containercarousel);
    }
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
  },
  methods: {
    /**
     * Get images from Media library related to process.
     */
    getLaunchpadImages() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.process.id}`)
        .then((response) => {
          const firstResponse = response.data.shift();
          const mediaArray = firstResponse.media;
          const embedArray = firstResponse.embed;
          mediaArray.forEach((media) => {
            const mediaType = media.custom_properties.type ?? "image";
            this.images.push({
              url: media.original_url,
              type: mediaType,
            });
          });
          embedArray.forEach((embed) => {
            const customProperties = JSON.parse(embed.custom_properties);
            this.images.push({
              url: customProperties.url,
              type: customProperties.type,
            });
          });
          // If no images were loaded Carousel container is not shown
          if (this.images.length === 0) {
            this.imagesLoaded = false;
          }
          // If only one image is loaded, rest of carousel must be completed with default image
          if (this.images.length === 1) {
            for (let i = 1; i <= 3; i++) {
              this.images[i] = {
                url: "/img/launchpad-images/defaultImage.svg",
                type: "image",
              };
            }
          }
        })
        .catch((error) => {
          console.error(error);
        });
    },
    updateSlideWidth() {
      if (this.$refs.slides?.[0]) {
        this.slideWidth = this.$refs.slides[0].offsetWidth + 11;
        this.translateX = this.slideWidth * -this.currentIndex - 10;
      }
    },
    prevSlide() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
        this.translateX += this.slideWidth;
      }
    },
    nextSlide() {
      if (this.currentIndex < this.slideCount - 1) {
        this.currentIndex++;
        this.translateX -= this.slideWidth;
      }
    },
  },
};
</script>
<style lang="scss" scoped>
.carousel-inner {
  overflow: hidden;
}

.img-carousel {
  width: 100%;
  height: auto;
  aspect-ratio: 16/9;
  object-fit: fill;
  border-radius: 16px;
}
.iframe-carousel {
  width: 100%;
  aspect-ratio: 16/9;
  object-fit: fill;
  border-radius: 16px;
}

@media (width <= 2560px) {
  .img-carousel {
  }
}
@media (width <= 1920px) {
  .img-carousel {
  }
}
@media (width <= 1440px) {
  .img-carousel {
  }
}
@media (width <= 1280px) {
  .img-carousel {
    height: auto;
  }
}
@media (width <= 1024px) {
  .img-carousel {
    height: auto;
  }
}
@media (width <= 768px) {
  .img-carousel {
    height: auto;
  }
}
@media (width <= 640px) {
  .img-carousel {
    height: auto;
  }
}
</style>
<style>
.carousel {
  position: relative;
  overflow: hidden;
  container-type: inline-size;
}
.slides {
  display: flex;
  transition: transform 0.3s ease;
}
.slide {
  flex: 0 0 auto;
  width: 100%;
  padding-left: 10px;
  padding-right: 10px;
}
.content {
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #ccc;
}

.prev,
.next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  cursor: pointer;
  color: #556271;
}
.prev {
  left: -10px;
  background-color: #fff;
}
.next {
  right: 4px;
  background-color: #fff;
}
@media (min-width: 640px) {
  .slide {
    width: var(--var-sizes-sm);
  }
}
@media (min-width: 768px) {
  .slide {
    width: var(--var-sizes-md);
  }
}
@media (min-width: 1024px) {
  .slide {
    width: var(--var-sizes-lg);
  }
}
@media (min-width: 1280px) {
  .slide {
    width: var(--var-sizes-xl);
  }
}
@media (min-width: 1536px) {
  .slide {
    width: var(--var-sizes-2xl);
  }
}
@media (min-width: 1920px) {
  .slide {
    width: var(--var-sizes-3xl);
  }
}
</style>
