<template>
  <b-card
    v-if="showCards"
    overlay
    class="card-process"
    @click="openInfo(process)"
  >
    <b-card-text>
      <div
        class="card-info"
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
      <div class="requests-count" v-if="caseCount">
        {{ caseCount }}
      </div>
      <bookmark
        v-if="!hideBookmark"
        :process="process"
        class="bookmark"
        @bookmark-updated="callLoadCard"
      />
    </b-card-text>
  </b-card>
  <b-card v-else
  class="d-flex text-center align-items-center justify-content-center card-process2">
    <span v-if="cardMessage === 'show-page'">Page {{ currentPage }} of {{ totalPages }}</span>
    <span v-if="cardMessage === 'show-more' && !loading"> {{ $t('Show More') }}</span>
    <span v-if="loading"><i class="fas fa-spinner fa-spin"></i> {{ $t('Loading') }}...</span>
  </b-card>
</template>

<script>

import Bookmark from "../Bookmark.vue";

export default {
  components: {
    Bookmark
  },
  props: {
    loading: false,
    cardMessage: null,
    currentPage: {
      type: Number,
      default: 1,
    },
    totalPages: {
      type: Number,
      default: 0
    } ,
    process: null,
    hideBookmark: {
      type: Boolean,
      default: false
    },
    showCards: true,
  },
  data() {
    return {
      labelIcon: "Default Icon",
      labelTooltip: "",
    };
  },
  computed: {
    caseCount() {
      if (this.process?.counts?.total) {
        return this.process.counts.total.toLocaleString();
      }
      return null;
    },
  },
  methods: {
    /**
     * Open the process
     */
    openInfo(process) {
      this.$emit("openProcessInfo", process);
    },
    getIconProcess() {
      let icon = "Default Icon";
      const unparseProperties = this.process.launchpad?.properties || null;
      if (unparseProperties !== null) {
        icon = JSON.parse(unparseProperties)?.icon || "Default Icon";
      }

      return `/img/launchpad-images/icons/${icon}.svg`;
    },
    callLoadCard() {
      this.$emit('callLoadCard', () => {}, 'bookmark');
    },
  },
};
</script>

<style scoped lang="scss">

@import '~styles/variables';

.card-process {
  max-width: 343px;
  min-width: 300px;
  width: 27vw;
  height: 232px;
  margin-top: 1rem;
  margin-left: 1rem;
  border-radius: 16px;
  background-image: url("/img/launchpad-images/process_background.svg");

  @media (max-width: $lp-breakpoint) {
    width: 100%;
    max-width: none;
    min-width: none;
    border-radius: 8px;
    height: 72px;
    margin-right: 5px;
    margin-left: 0;
    background-image: none;
  }
}

.card-process2 {
  height: 40px;
  margin-top: 1rem;
  margin-right: 7%;
  border-radius: 8px;
  background-color: #E5EDF3;
  margin-left: 1rem;

  @media (max-width: $lp-breakpoint) {
    width: 100%;
    max-width: none;
    min-width: none;
    border-radius: 8px;
    height: 40px;
    margin-right: 0;
  }
}
.card-process:hover {
  box-shadow: 0px 3px 16px 2px #acbdcf75;
}
.card-body {
  padding: 32px;
  height: 100%;
  width: 100%;
  margin-bottom: 20px;

  @media (max-width: $lp-breakpoint) {
    padding-left: 16px;
    padding-right: 20px;
  }
}
.card-img {
  border-radius: 16px;
}
.requests-count {
  display: none;
  float: right;
  font-size: 14px;
  font-weight: bold;
  background-color: #F9E7C3;
  margin-right: 8px;
  
  border-radius: 12px;
  min-width: 24px;
  height: 24px;
  justify-content: center;
  align-items: center;
  padding: 0 8px;

  @media (max-width: $lp-breakpoint) {
    display: flex;
  }
}

.bookmark {
  float:right;
  font-size: 1.2em;
}
.card-text {
  height: 100%;
  display: flex;
  width: 100%;
  
  @media (max-width: $lp-breakpoint) {
    align-items: center;
  }
}
.card-info {
  cursor: pointer;
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: baseline;
  justify-content: flex-end;

  @media (max-width: $lp-breakpoint) {
    flex-direction: row;
    align-items: center;
    justify-content: normal;
  }
}
.icon-process {
  width: 40px;
  height: 48px;
  margin-bottom: 16px;

  @media (max-width: $lp-breakpoint) {
    margin-bottom: 0;
    margin-right: 10px;  
    width: 24px;
    height: 24px;
  }
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
  word-break: break-word;
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
