<template>
  <b-card
    img-src="/img/launchpad-images/process_background.svg"
    img-alt="Card Image"
    overlay
    class="card-process"
  >
    <b-card-text>
      <div class="card-bookmark">
        <i
          :ref="`bookmark-${process.id}`"
          :class="bookmarkIcon()"
          @click="checkBookmark(process)"
        />
      </div>
      <div
        class="card-info"
        @click="openInfo(process)"
      >
        <img
          class="icon-process"
          src="/img/default-process.svg"
          :alt="$t('Default Icon')"
        >
        <span class="title-process">{{ process.name }}</span>
      </div>
    </b-card-text>
  </b-card>
</template>

<script>
export default {
  props: ["process"],
  data() {
    return {
      bookmarkIcon_: "far fa-bookmark",
      bookmarkedIcon_: "fas fa-bookmark",
    };
  },
  methods: {
    /**
     * Check the bookmark to add bookmarked list or remove it
     */
    checkBookmark(process) {
      if (process.bookmark_id) {
        ProcessMaker.apiClient
          .delete(`process_bookmarks/${process.bookmark_id}`)
          .then(() => {
            ProcessMaker.alert(this.$t("Process removed from Bookmarked List."), "success");
            this.$parent.loadCard();
          });
        return;
      }
      ProcessMaker.apiClient
        .post(`process_bookmarks/${process.id}`)
        .then(() => {
          ProcessMaker.alert(this.$t("Process added to Bookmarked List."), "success");
          this.$parent.loadCard();
        });
    },
    /**
     * Verify if the process is marked
     */
    bookmarkIcon() {
      if (this.process.bookmark_id !== 0) {
        return "fas fa-bookmark marked";
      }
      return "far fa-bookmark";
    },
    /**
     * Open the process
     */
    openInfo(process) {
      this.$emit("openProcessInfo", process);
    },
  },
};
</script>

<style scoped>
.card-process {
  width: 350px;
  height: 240px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 16px;
}
.card-img {
  border-radius: 16px;
}
.card-bookmark {
  float: right;
  font-size: 20px;
}
.card-bookmark:hover {
  cursor: pointer;
}
.card-info {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: baseline;
  padding-top: 15%;
}
.icon-process {
  font-size: 68px;
  margin-bottom: 1rem;
}
.marked {
  color: #1372c4;
}
.title-process {
  color: #556271;
  font-family: Poppins, sans-serif;
  font-size: 20px;
  font-style: normal;
  font-weight: 700;
  line-height: normal;
  letter-spacing: -0.4px;
  text-transform: uppercase;
}
</style>
