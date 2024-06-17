<template>
    <i
      v-b-tooltip.hover.bottom
      :title="bookmarkTitle"
      :class="bookmarkClass"
      @click="checkBookmark()"
    />
</template>

<script>
export default {
  props: {
    process: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      bookmarkId: null,
    };
  },
  mounted() {
    this.bookmarkId = this.process?.bookmark_id;
  },
  methods: {
    checkBookmark() {
      if (this.isBookmarked) {
        ProcessMaker.apiClient
          .delete(`process_bookmarks/${this.bookmarkId}`)
          .then(() => {
            ProcessMaker.alert(this.$t("Process removed from Bookmarked List."), "success");
            this.$emit('bookmark-updated', false);
            this.bookmarkId = null;
          });
        return;
      }
      ProcessMaker.apiClient
        .post(`process_bookmarks/${this.process.id}`)
        .then(response => {
          console.log(response);
          ProcessMaker.alert(this.$t("Process added to Bookmarked List."), "success");
          this.$emit('bookmark-updated', true);
          this.bookmarkId = response.data.newId;
        });
    },
  },
  computed: {
    isBookmarked() {
      return this.bookmarkId && this.bookmarkId !== 0;
    },
    bookmarkClass() {
      return this.isBookmarked ? "fas fa-bookmark marked bookmark" : "fas fa-bookmark unmarked bookmark";
    },
    bookmarkTitle() {
      return this.isBookmarked ? this.$t("Remove from My Bookmarks") : this.$t("Add to My Bookmarks");
    },

  }
}
</script>

<style lang="scss" scoped>
@import '~styles/variables';
.bookmark {
  font-size: 1.5em;
}
.bookmark:hover {
  cursor: pointer;
}

.marked {
  color: #f5bC00;
}
.unmarked {
  color: #fff;
  -webkit-text-stroke-color: #bed1e5;
  -webkit-text-stroke-width: 2px;
}
.unmarked:hover {
  @media (max-width: $lp-breakpoint) {
    color: #fff;
  }
  color: #ffd445;
  -webkit-text-stroke-width: 0;
}
</style>