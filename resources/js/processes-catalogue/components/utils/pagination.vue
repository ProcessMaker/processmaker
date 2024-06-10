<template>
  <div class="pagination">
    <b-pagination
      v-if="totalRow > 0"
      v-model="currentPage"
      :total-rows="totalRow"
      :per-page="perPage"
      :limit="limit"
      class="template-modal-pagination"
      prev-class="caretBtn prevBtn"
      next-class="caretBtn nextBtn"
      size="sm"
      @change="onPageChanged"
    />
  </div>
</template>

<script>
export default {
  props: ["totalRow", "perPage"],
  data() {
    return {
      currentPage: 1,
      limit: 7,
    };
  },
  methods: {
    onPageChanged(page) {
      this.currentPage = page;
      this.$emit("onPageChanged", page);
    },
  },
};
</script>

<style scoped>
.pagination {
  position: relative;
  bottom: 0;
  width: 100%;
  margin-top: 0;
}
</style>
