<template>
  <b-card v-if="showCards" class="m-3">
    <a :href="openURL">
      <b-card-text @click="openCard()">
        <b-row>
          <b-col cols="9">
            <span class="titleInfo">
              <template v-if="type === 'tasks'">
                {{ item.element_name }}
              </template>
              <template v-if="type === 'requests'">
                {{ item.name }}
              </template>
            </span>
          </b-col>
          <b-col
            cols="3"
            class="align-left"
          >
            <span>
              #{{ item.id }}
            </span>
          </b-col>
        </b-row>
        <div class="mt-3 mb-3">
          <template v-if="type === 'tasks'">
            <b-link href="">
              #{{ item.process_request.id }}
              {{ item.process.name }}
            </b-link>
          </template>
          <template v-if="type === 'requests'">
            <span class="dateInfo">
              {{ $t("Started") }}: {{ formatDate(item.initiated_at) }}
            </span>
          </template>
        </div>
        <b-row align-h="between">
          <b-col cols="4">
            <b-badge
              pill
              style="color: #44494E"
              :style="colorStatus"
            >
              {{ showBadge(item) }}
            </b-badge>
          </b-col>
          <b-col
            v-if="type === 'requests'"
            cols="4"
            class="align-left"
          >
            <avatar-image
              v-for="participant in item.participants"
              :key="participant.id"
              size="25"
              hide-name="true"
              :input-data="participant"
            />
          </b-col>
        </b-row>
      </b-card-text>
    </a>
  </b-card>
  <b-card v-else
    class="card-tasks">
    <span v-if="cardMessage === 'show-page'">Page {{ currentPage }} of {{ totalPages }}</span>
    <span v-if="cardMessage === 'show-more' && !loading"> {{ $t('Show More') }}</span>
    <span v-if="loading"><i class="fas fa-spinner fa-spin"></i> {{ $t('Loading') }}...</span>
  </b-card>
</template>

<script>
import AvatarImage from "../components/AvatarImage.vue";

export default {
  components: { AvatarImage },
  //props: ["type", "item"],
  props: {
    item: null,
    type: null,
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
      openURL: "",
      colorStatus: "",
      requestBadge: "",
      taskStatus: "",
    };
  },
  methods: {
    /**
     * Show info in the badge
     */
    showBadge(item) {
      if (this.type === "tasks") {
        if (item.due_notified === 1) {
          this.colorStatus = "background-color: #FFF5DB";
          this.taskStatus = "Due: ";
          return this.taskStatus + this.formatDate(item.due_at);
        }
        this.taskStatus = "Done: ";
        this.colorStatus = "background-color: #B8F2DF";
        return this.taskStatus + this.formatDate(item.created_at);
      }
      switch (this.item.status) {
        case "DRAFT":
          this.colorStatus = "background-color: #ffdbdb";
          this.requestBadge = "Draft";
          break;
        case "CANCELED":
          this.colorStatus = "background-color: #ffdbdb";
          this.requestBadge = "Canceled";
          break;
        case "COMPLETED":
          this.colorStatus = "background-color: #B8F2DF";
          this.requestBadge = "Completed";
          break;
        case "ERROR":
          this.colorStatus = "background-color: #ffdbdb";
          this.requestBadge = "Error";
          break;
        default:
          this.colorStatus = "background-color: #FFF5DB";
          this.requestBadge = "In Progress";
          break;
      }
      return this.requestBadge;
    },
    /**
     * Format the date
     */
    formatDate(value, format) {
      const formatDate = format || "";
      if (value) {
        return window.moment(value)
          .format(formatDate);
      }
      return "n/a";
    },
    openCard() {
      if (this.type === "tasks") {
        this.openURL = `/tasks/${this.item.id}/edit`;
      }
      if (this.type === "requests") {
        this.openURL = `/requests/${this.item.id}`;
      }
    },
  },
};
</script>

<style scoped>
a,
.dateInfo {
  color: #57646F;
}
.titleInfo {
  color: #1572C2;
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
}
.badge-custom {
  color: #44494E;
  background-color: #FFF5DB;
}
.align-left {
  text-align: end;
}
.card-tasks {
  height: 40px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 8px;
  background-color: #E5EDF3;
  align-items: center;
  margin-left: 1rem;
}
</style>
