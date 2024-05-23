<template>
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
        }">
        <div
            class="slides"
            :style="{ transform: 'translateX(' + translateX + 'px)' }">
            <div
                class="slide"
                v-for="(image, index) in images.length > 0 ? images : defaultImage"
               :key="index">
                <!-- <div
                    ref="slides"
                    class="content">
                    {{ image.url }}
                </div> -->
                <img
                  ref="slides"
                  :src="image.url"
                  :alt="process.name"
                  class="content"
                >
            </div>
        </div>
        <button
            class="prev"
            @click="prevSlide">
            Prev
        </button>
        <button
            class="next"
            @click="nextSlide">
            Next
        </button>
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
      resizeObserver: null,
      items: [
        { id: 1, text: "Slide 1" },
        { id: 2, text: "Slide 2" },
        { id: 3, text: "Slide 3" },
        { id: 1, text: "Slide 4" },
        { id: 2, text: "Slide 5" },
        { id: 3, text: "Slide 6" },
        { id: 1, text: "Slide 7" },
        { id: 2, text: "Slide 8" },
        { id: 3, text: "Slide 9" },
        { id: 1, text: "Slide 10" },
        { id: 2, text: "Slide 11" },
        { id: 3, text: "Slide 12" },
        // Add more items as needed
      ],
      currentIndex: 0,
      slideWidth: 0,
      translateX: 0,
      sizes: {
        sm: 1,
        md: 2,
        lg: 3,
        xl: 3,
        "2xl": 3,
      },
    };
  },
  computed: {
    slideCount() {
      return this.items.length;
    },
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
    this.slideWidth = this.$refs.slides[0].offsetWidth;
    this.resizeObserver = new ResizeObserver(this.updateSlideWidth);
    this.resizeObserver.observe(this.$refs.containercarousel);
  },
  destroyed() {
    this.resizeObserver.unobserve(this.$refs.containercarousel);
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
        })
        .catch((error) => {
          console.error(error);
        });
    },
    updateSlideWidth() {
      this.slideWidth = this.$refs.slides[0].offsetWidth;
      this.translateX = this.slideWidth*(-this.currentIndex);
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
  max-width: 800px;
  height: 410px;
  aspect-ratio: 16/9;
}
.iframe-carousel {
  border: 0px;
  width: 800px;
  height: 400px;
}
.carousel-container {
  display: flex;
  justify-content: center;
  border-radius: 16px;
  background-color: #edf1f6;
}
@media (width <= 1500px) {
  .img-carousel {
    max-width: 700px;
  }
}
@media (width <= 1366px) {
  .img-carousel {
    max-width: 590px;
  }
}
@media (width <= 1200px) {
  .img-carousel {
    max-width: 513px;
    height: auto;
  }
}
@media (width <= 992px) {
  .img-carousel {
    max-width: 486px;
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
}

.content {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px; /* Adjust height as needed */
    background-color: #ccc;
}

.prev,
.next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: #333;
    color: #fff;
    border: none;
    padding: 10px;
    cursor: pointer;
}

.prev {
    left: 0;
}

.next {
    right: 0;
}

@container (min-width: 640px) {
    .slide {
        width: var(--var-sizes-sm);
    }
}

@container (min-width: 768px) {
    .slide {
        width: var(--var-sizes-md);
    }
}

@container (min-width: 1024px) {
    .slide {
        width: var(--var-sizes-lg);
    }
}

@container (min-width: 1280px) {
    .slide {
        width: var(--var-sizes-xl);
    }
}

@container (min-width: 1536px) {
    .slide {
        width: var(--var-sizes-2xl);
    }
}
</style>
