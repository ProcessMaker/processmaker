<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="tw-flex tw-items-center tw-justify-start">
    <nav class="tw-inline-flex tw-overflow-hidden tw-rounded-lg tw-border tw-border-gray-300 tw-bg-white">
      <button
        type="button"
        class="tw-px-3 tw-py-1.5 tw-text-sm tw-text-black tw-font-semibold"
        :disabled="page === 1"
        @click="handlePrev"
      >
        Previous
      </button>
      <button
        v-for="(p, idx) in displayPages"
        :key="`p-${idx}-${p}`"
        type="button"
        class="tw-border-l tw-border-zinc-300 tw-px-3 tw-py-1.5 tw-text-sm tw-text-black tw-font-semibold"
        :class="
          typeof p === 'number'
            ? (p === page ? 'tw-bg-zinc-100 tw-text-zinc-900' : 'tw-text-zinc-700 hover:tw-bg-zinc-50')
            : 'tw-text-zinc-400'
        "
        :disabled="p === '...'"
        @click="typeof p === 'number' && handleGo(p)"
      >
        {{ p }}
      </button>
      <button
        type="button"
        class="tw-border-l tw-border-zinc-300 tw-px-3 tw-py-1.5 tw-text-sm tw-text-black tw-font-semibold"
        :disabled="page === totalPages"
        @click="handleNext"
      >
        Next
      </button>
    </nav>
  </div>
</template>

<script>
export default {
  props: {
    page: { type: Number, required: true },
    totalPages: { type: Number, required: true },
  },
  computed: {
    displayPages() {
      const pages = [];
      const current = this.page;
      const total = this.totalPages;
      if (total <= 7) {
        for (let i = 1; i <= total; i += 1) pages.push(i);
        return pages;
      }

      const add = (val) => {
        if (pages[pages.length - 1] !== val) pages.push(val);
      };

      add(1);
      if (current > 3) add('...');

      const start = Math.max(2, current - 1);
      const end = Math.min(total - 1, current + 1);
      for (let i = start; i <= end; i += 1) add(i);

      if (current < total - 2) add('...');
      add(total);
      return pages;
    },
  },
  methods: {
    handleGo(pageNumber) {
      this.$emit('page-change', pageNumber);
    },
    handlePrev() {
      if (this.page > 1) {
        this.$emit('page-change', this.page - 1);
      }
    },
    handleNext() {
      if (this.page < this.totalPages) {
        this.$emit('page-change', this.page + 1);
      }
    },
  },
};
</script>

