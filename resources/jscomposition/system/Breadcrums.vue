<template>
  <nav class="tw-flex tw-text-xs tw-text-gray-400 hover:tw-text-gray-500">
    <ol class="tw-flex tw-items-center tw-space-x-1">
      <li>
        <div>
          <a href="#" class="">
            <i class="fa-solid fa-house"></i>
            <span class="sr-only">Home</span>
          </a>
        </div>
      </li>
      <li v-for="page in pages" :key="page.name">
        <div class="tw-flex tw-items-center">
          <svg
            class="tw-h-5 tw-w-5 tw-flex-shrink-0"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true">
            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
          </svg>
          <a
            :href="page.href"
            class="tw-text-gray-500 hover:tw-text-gray-700"
            :aria-current="page.current ? 'page' : undefined"
            >{{ page.name }}</a
          >
        </div>
      </li>
    </ol>
  </nav>
</template>
<script>
import { defineComponent } from "vue";

export default defineComponent({
  setup() {
    const pages = [
      { name: "Projects", href: "#", current: false },
      { name: "Project Nero", href: "#", current: true },
    ];
    return {
      pages,
    };
  },
});
</script>
