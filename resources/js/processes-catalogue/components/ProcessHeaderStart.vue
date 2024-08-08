<template>
  <div>
    <div class="header-mobile">
      <div class="title">
        {{ process.name }}
      </div>
    </div>
    <div class="header card card-body card-custom">
      <div class="d-flex justify-content-between">
        <div class="d-flex align-items-center flex-grow-1">
          <i class="fas fa-chevron-left mr-2 custom-color" 
            @click="$emit('goBack')" />
          <div class="title text-truncate" v-b-tooltip.hover :title="process.name">
            {{ process.name }}
          </div>
        </div>
        <div class="d-flex align-items-center flex-shrink-0">
          <div class="card-bookmark mx-2">
            <bookmark :process="process" />
          </div>
          <span class="ellipsis-border">
            <ellipsis-menu 
              v-if="showEllipsis" 
              :actions="processLaunchpadActions" 
              :data="process" 
              :divider="false"
              :lauchpad="true" 
              variant="none" 
              @navigate="ellipsisNavigate"
              :isDocumenterInstalled="$root.isDocumenterInstalled" 
              :permission="$root.permission" />
          </span>
          <buttons-start :process="process" :startEvent="singleStartEvent" :processEvents="processEvents" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import ProcessesMixin from "./mixins/ProcessesMixin";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import Bookmark from "./Bookmark.vue";

export default {
  components: {
    ButtonsStart,
    EllipsisMenu,
    Bookmark,
  },
  mixins: [
    ProcessesMixin,
    ellipsisMenuMixin
  ],
  props: {
    process: {
      type: Object,
      required: true
    },
    enableCollapse: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      infoCollapsed: true,
      processEvents: [],
      singleStartEvent: null,
    }
  },
  mounted() {
    this.getStartEvents();
  },
  methods: {
    ellipsisNavigate(action, data) {
      this.$emit("onProcessNavigate", action, data);
    },
    toggleInfoCollapsed() {
      this.infoCollapsed = !this.infoCollapsed;
    },
    /**
     * get start events for dropdown Menu
     */
    getStartEvents() {
      this.processEvents = [];
      ProcessMaker.apiClient
        .get(`process_bookmarks/processes/${this.process.id}/start_events`)
        .then((response) => {
          this.processEvents = response.data.data;
          if (this.processEvents.length === 0) {
            ProcessMaker.alert(this.$t('The current user does not have permission to start this process'), "danger");
          }
          const nonWebEntryStartEvents = this.processEvents.filter(e => !("webEntry" in e) || !e.webEntry);
          if (nonWebEntryStartEvents.length === 1) {
            this.singleStartEvent = nonWebEntryStartEvents[0].id;
          }
        })
        .catch((err) => {
          ProcessMaker.alert(err, "danger");
        });
    },
  }
}
</script>
<style>
.ellipsis-border div button span {
  font-size: 16px;
}
</style>
<style lang="scss" scoped>
@import url("./scss/processes.css");
@import '~styles/variables';


.header-mobile .title,
.header .title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
}

.flex-grow-1 {
  flex-grow: 1;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.d-flex.align-items-center {
  min-width: 0;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}

.header {
  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}

.header-mobile {
  display: none;
  padding: 1em;

  .title {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 16px;
    color: #4C545C;
    font-weight: 400;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
    flex-direction: row;
    align-items: center;
  }
}

.card-bookmark {
  float: right;
  font-size: 20px;
}

.card-bookmark:hover {
  cursor: pointer;
}

.card-custom {
  background-color: #F6F9FB;
  border: 1px solid rgba(205, 221, 238, 0.125);
}

.title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
  max-width: 100%;
  font-size: 22px;
  letter-spacing: -0.2;
  color: #4C545C;
  font-weight: 400;
}

.custom-color {
  color: #4C545C;
}
</style>