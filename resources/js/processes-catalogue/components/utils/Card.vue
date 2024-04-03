<template>
  <b-card
    overlay
    class="card-process"
  >
    <b-card-text>
      <div class="card-bookmark">
        <i
          :ref="`bookmark-${process.id}`"
          v-b-tooltip.hover.bottom
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
        <span
          :id="`title-${process.id}`"
          class="title-process"
        >
          {{ process.name }}
        </span>
        <b-popover
          v-if="process.name.length > 120"
          :target="`title-${process.id}`"
          placement="bottom"
          triggers="hover focus"
          :content="process.name"
          variant="custom"
        />
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
      return "fas fa-bookmark unmarked";
    },
    /**
     * Open the process
     */
    openInfo(process) {
      this.$emit("openProcessInfo", process);
    },
    getIconProcess() {
      let icon = "Default Icon";
      if (this.process.launchpad.properties) {
        icon = JSON.parse(this.process.launchpad.properties)?.icon || "Default Icon";
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
  width: 27vw;
  height: 232px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 16px;
  background-image: url("/img/launchpad-images/process_background.svg");
}
.card-process:hover {
  box-shadow: 0px 3px 16px 2px #acbdcf75;
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
  font-size: 24px;
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
  justify-content: flex-end;
}
.icon-process {
  width: 48px;
  height: 48px;
  margin-bottom: 16px;
}
.marked {
  color: #f5bC00;
}
.unmarked {
  color: #ebf3f7;
  -webkit-text-stroke-color: #bed1e5;
  -webkit-text-stroke-width: 1px;
}
.unmarked:hover {
  color: #ffd445;
  -webkit-text-stroke-width: 0;
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
.b-popover-custom.popover {
  background-color: #F6F9FB;
  border-radius: 4px;
  border: 1px solid #CDDDEE;
  box-shadow: 0px 10px 20px 4px #00000021;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: -0.02em;
  text-align: left;
  padding: 20px;
}
</style>
