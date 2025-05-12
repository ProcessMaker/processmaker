<template>
  <transition name="slide-fade">
    <div
      v-if="show"
      :style="{'height': 'calc(100vh - 145px)'}"
      :class="fullCarousel ? 'tw-w-full' : 'tw-w-1/2'"
      class="tw-absolute tw-top-0 tw-right-0 tw-h-full tw-bg-white tw-shadow-lg tw-z-10 tw-overflow-y-auto"
    >
      <div class="tw-flex tw-flex-col tw-h-full">
        <div class="tw-flex tw-justify-between tw-items-center tw-px-5 tw-py-4 tw-border-b tw-border-gray-200 tw-bg-gray-50">
          <h3 class="tw-m-0 tw-text-lg tw-font-medium tw-text-gray-700">
            <b-button
              v-if="fullCarousel"
              variant="light"
              @click="closeFullCarousel"
            >
              <i class="fas fa-angle-left tw-text-gray-500 tw-mr-2" />
            </b-button>

            {{ title }}
          </h3>
          <button
            class="tw-bg-transparent tw-border-none
              tw-text-lg tw-text-gray-600 tw-cursor-pointer
              tw-p-1.5 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-8 tw-h-8
              tw-transition-colors hover:tw-bg-gray-100 hover:tw-text-blue-500"
            @click="closeSlide"
          >
            <i class="fas fa-times" />
          </button>
        </div>
        <div class="tw-flex-1 tw-p-5 tw-overflow-y-auto">
          <slot />
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: "SlideProcessInfo",
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Process Information",
    },
    fullCarousel: {
      type: Boolean,
      default: false,
    },
    process: {
      type: Object,
      required: true,
    },
  },
  methods: {
    closeSlide() {
      this.$emit("close");
    },
    closeFullCarousel() {
      this.$emit("closeCarousel");
    },
  },
};
</script>

<style lang="scss" scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-fade-enter,
.slide-fade-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
