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
        class="header card card-body"
      >
        <div class="d-flex justify-content-between">
          <div class="d-flex align-items-center">
            <i
              class="fas fa-arrow-left text-secondary mr-2 iconTitle"
              @click="$emit('goBack')"
            />
            <button
              v-if="enableCollapse"
              class="btn border-0 header-process title-process-button"
              type="button"
              data-toggle="collapse"
              data-target="#collapseProcessInfo"
              aria-controls="collapseProcessInfo"
              :aria-expanded="infoCollapsed"
              @click="toggleInfoCollapsed()"
            >
              <template v-if="infoCollapsed">
                {{ $t('Process Info') }}
                <i class="fas fa-angle-up pl-2" />
              </template>
              <template v-else>
                {{ getNameEllipsis() }}
                <i class="fas fa-angle-down pl-2" />
              </template>
            </button>
            <template v-else>
              {{ getNameEllipsis() }}
            </template>

          </div>
          <div class="d-flex align-items-center">
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
    font-size: 1.5em;
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
</style>