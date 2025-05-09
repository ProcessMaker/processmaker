<template>
  <div
    v-show="imagesLoaded"
    id="carouselwrapper"
    class="h-100 w-100 custom-fit"
  >
    <button
      :class="[fullPage ? 'prev-full' : 'prev']"
      @click="prevSlide"
    >
      <i class="fas fa-caret-left" />
    </button>
    <button
      :class="[fullPage ? 'next-full' : 'next']"
      @click="nextSlide"
    >
      <i class="fas fa-caret-right" />
    </button>

    <div
      ref="containercarousel"
      class="carousel"
      @resize="updateSlideWidth"
    >
      <div
        ref="slidesDiv"
        class="slides"
        :style="{ transform: 'translateX(' + translateX + 'px)' }"
      >
        <div
          v-for="(image, index) in images.length > 0 ? images : defaultImage"
          ref="slidesDivChild"
          :key="index"
          class="slide"
        >
          <iframe
            v-if="image.type === 'embed'"
            ref="slides"
            :class="['content', fullPage ? 'iframe-carousel-full' : 'iframe-carousel']"
            :src="image.url"
            title="embed media"
            @click="handleClick(image.url, index)"
          />
          <img
            v-else
            ref="slides"
            :class="['content', fullPage ? 'img-carousel-full' : 'img-carousel']"
            :src="image.url"
            :alt="process.name"
            @click="handleClick(image.url, index)"
          >
        </div>
      </div>
    </div>
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
    fullCarousel: {
      type: Object,
      default: null,
    },
    indexSelectedImage: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      discountSlide: 1,
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
      fullPage: false,
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
      return this.images.length - this.discountSlide;
    },
  },
  watch: {
    "fullCarousel.hideLaunchpad": {
      immediate: true,
      handler(value) {
        if (value) {
          this.sizes = {
            sm: 1,
            md: 1,
            lg: 1,
            xl: 1,
            "2xl": 1,
            "3xl": 1,
          };
          this.discountSlide = 0;
          this.fullPage = true;
        }
      },
    },
    indexSelectedImage() {
      this.currentIndex = 0;
      this.currentIndex = this.indexSelectedImage - 1;
      this.nextSlide();
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
      },
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
    handleClick(url, index) {
      this.$root.hasClickSlide = true;
      if (!this.fullPage) {
        const data = {
          url,
          hideLaunchpad: true,
          countImages: this.images.length,
          imagePosition: index,
        };
        this.$root.$emit("clickCarouselImage", data);
      }
    },
    updateSlideWidth() {
      if (this.$refs.slides?.[0]) {
        this.slideWidth = this.$refs.slides[0].offsetWidth + 11;
        this.translateX = this.slideWidth * -this.currentIndex - 10;
      }
    },
    prevSlide() {
      if (this.currentIndex > 0) {
        this.currentIndex -= 1;
        this.translateX += this.slideWidth;
        this.$root.$emit("carouselImageSelected", this.currentIndex);
      }
    },
    nextSlide() {
      let slidesWidth = getComputedStyle(this.$refs.slidesDiv).getPropertyValue("width");
      let slidesWidthChild = getComputedStyle(this.$refs.slidesDivChild[0]).getPropertyValue("width");
      slidesWidth = parseInt(slidesWidth, 10);
      slidesWidthChild = parseInt(slidesWidthChild, 10);
      let n = this.slideCount - 1;
      let optionClicked = true;
      if (slidesWidth === slidesWidthChild) {
        n = this.slideCount;
      }

      const currentTranslateX = this.translateX;
      const { slideWidth } = this;
      const newTranslateX = currentTranslateX - slideWidth;
      const slideWidthTotal = (-1) * slideWidth * this.slideCount;
      if (this.$root.hasClickSlide) {
        optionClicked = (newTranslateX > slideWidthTotal);
      }

      if ((this.currentIndex < n) && optionClicked) {
        this.currentIndex += 1;
        this.translateX -= this.slideWidth;
        this.$root.$emit("carouselImageSelected", this.currentIndex);
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
  object-fit: cover;
  border-radius: 16px;
}
.iframe-carousel {
  width: 100%;
  aspect-ratio: 16/9;
  object-fit: cover;
  border-radius: 16px;
}

.img-carousel-full {
  width: 100%;
  height: auto;
  aspect-ratio: 16/9;
  object-fit: cover;
  border-radius: 16px;
  margin-top: 2%;
  margin-bottom: 2%;
}
.iframe-carousel-full {
  width: 100%;
  aspect-ratio: 16/9;
  object-fit: cover;
  border-radius: 16px;
  margin-top: 2%;
  margin-bottom: 2%;
}

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

.prev {
  left: 0px;
  background-color: #fff;
  position: absolute;
  top: 10%;
  transform: translateY(-50%);
  border: none;
  cursor: pointer;
  color: #556271;
}
.next {
  right: 0%;
  background-color: #fff;
  position: absolute;
  top: 0%;
  transform: translateY(-50%);
  border: none;
  cursor: pointer;
  color: #556271;
}

.prev-full {
  left: -6px;
  background-color: #f7f9fb;
  position: absolute;
  top: 60%;
  transform: translateY(-50%);
  border: none;
  cursor: pointer;
  color: #556271;
}
.next-full {
  right: 2px;
  background-color: #f7f9fb;
  position: absolute;
  top: 60%;
  transform: translateY(-50%);
  border: none;
  cursor: pointer;
  color: #556271;
  z-index: 1;
}

#carouselwrapper:hover .prev,
#carouselwrapper:hover .next,
#carouselwrapper:hover .prev-full,
#carouselwrapper:hover .next-full {
  display: block;
}

.prev, .next,
.prev-full, .next-full {
  font-size: 20px;
  display: none;
}

.custom-fit {
  padding-left: 1%;
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
