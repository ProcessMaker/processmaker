<template>
  <div>
    <div class="header-mobile">
        <div class="title">
          {{ process.name }}
        </div>
        <div class="start-button">
          <buttons-start
            :process="process"
            :title="$t('Start')"
            :startEvent="singleStartEvent"
            :processEvents="processEvents"
          />
        </div>
      </div>
      <div
        class="header card-body card-process-info"
        data-toggle="collapse"
              data-target="#collapseProcessInfo"
              aria-controls="collapseProcessInfo"
              :aria-expanded="infoCollapsed"
        @click="toggleInfoCollapsed()"
      >
        <div class="d-flex justify-content-between">
          <div class="d-flex align-items-center">
              <template v-if="infoCollapsed">
                <i class="fas fa-caret-down pl-2 mr-2 custom-color" />
                <span class="custom-text">
                  {{ $t('Process Info') }}
                </span>
              </template>
              <template v-else>
                <i class="fas fa-caret-right pl-2 mr-2 custom-color" />
                <span class="custom-text">
                  {{ $t('Process Info') }}
                </span>
              </template>
          </div>
          <div v-if="!hideHeaderOptions" class="d-flex align-items-center">
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
                @navigate="$emit('onProcessNavigate')"
                :isDocumenterInstalled="$root.isDocumenterInstalled"
                :permission="$root.permission"
              />
            </span>
            <buttons-start
              :process="process"
              :startEvent="singleStartEvent"
              :processEvents="processEvents"
            />
          </div>
          <div v-else class="d-flex align-items-center">
            <template v-if="!infoCollapsed">
              <process-counter :process="process" :enable-collapse="enableCollapse" />
            </template>
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
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";

export default {
  components: {
    ButtonsStart,
    EllipsisMenu,
    Bookmark,
    ProcessCounter,
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
    },
    hideHeaderOptions: {
      type: Boolean,
      default: false
    },
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

<style lang="scss" scoped>
@import url("./scss/processes.css");
@import '~styles/variables';
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
    font-size: 22px;
    letter-spacing: -0.2;
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

.card-process-info {
  border-color: #CDDDEE;
  border-radius: 8px;
  background-color: #fff;
  margin-bottom: 12px;
  border: 1px solid rgb(205, 221, 238);
  padding-top: 13px;
  height: 53px;
  margin-right: 20px;
}
.custom-text {
  font-size: 16px;
  font-weight: 400;
  color: #556271;
  letter-spacing: -0.2;
}

.custom-color {
  color: #4C545C;
}
</style>