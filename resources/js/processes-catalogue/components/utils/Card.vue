<template>
  <b-card
    overlay
    class="card-process"
  >
    <b-card-text>
      <div class="card-bookmark">
        <i
          :ref="`bookmark-${process.id}`"
          v-b-tooltip.hover
          :title="$t(labelTooltip)"
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
          :src="getIconProcess()"
          :alt="$t(labelIcon)"
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
      labelIcon: "Default Icon",
      labelTooltip: "",
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
        this.labelTooltip = this.$t("Remove from My Bookmarks");
        return "fas fa-bookmark marked";
      }
      this.labelTooltip = this.$t("Add to My Bookmarks");
      return "far fa-bookmark";
    },
    /**
     * Open the process
     */
    openInfo(process) {
      this.$emit("openProcessInfo", process);
    },
    getIconProcess() {
      let icon = "default-icon";
      if (this.process.launchpad_properties) {
        icon = JSON.parse(this.process.launchpad_properties).icon || "default-icon";
      }
      return `/img/launchpad-images/icons/${icon}.svg`;
    },
  },
};
</script>

<style scoped>
.card-process {
  max-width: 343px;
  min-width: 296px;
  width: 330px;
  height: 232px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 16px;
  background-image: url("/img/launchpad-images/process_background_2.svg");
}
.card-process:hover {
  box-shadow: 0px 3px 16px 2px #ACBDCF75;
}
.card-body {
  padding: 32px;
  height: 100%;
  width: 100%;
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
.card-text {
  height: 100%;
}
.card-info {
  cursor: pointer;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: baseline;
  justify-content: end;
}
.icon-process {
  width: 48px;
  height: 48px;
  margin-bottom: 16px;
}
.marked {
  color: #1372c4;
}
.title-process {
  color: #556271;
  font-family: Poppins, sans-serif;
  font-size: 17px;
  font-style: normal;
  font-weight: 700;
  line-height: 23.15px;
  letter-spacing: -0.4px;
  text-transform: uppercase;
  display: -webkit-box;
  -webkit-line-clamp: 4;
  line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-all;
}
</style>
