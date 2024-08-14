<template>
  <b-card v-if="showCards" class="m-3 card-mobile" no-body>
    <a :href="openURL">
      <b-card-body
        class="card-mobile-body"
      >
        <b-card-text @click="openCard()">
          <b-row>
            <b-col cols="12">
              <span>
                <div
                  ref="fullText"
                  :style="{ display: isVisible ? 'block' : 'none' }"
                  v-html="sanitize(item.case_title_formatted)"
                />
                <div ref="line1" class="line-1" v-html="title1"></div>
                <div ref="line2" class="line-2" v-html="title2"></div>
              </span>
            </b-col>
          </b-row>
          <b-row class="justify-content-center">
            <div class="card-divider col-11" />
          </b-row>
          <div class="mt-3 mb-3">
            <template>
              <template v-for="(row, index) in fields">
                <div
                  :key="index"
                  class="bodyInfo"
                >
                  {{ $t(row.label) }}: {{ formatItem[row.field] }}
                </div>
              </template>
            </template>
          </div>
        </b-card-text>
      </b-card-body>
      <b-card-footer @click="openCard()" class="card-mobile-footer">
        <b-row align-h="between">
          <b-col cols="4">
            <img
              src="/img/smartinbox-images/open-case.svg"
              alt="case_number"
            >
            <span class="footer-case-number">
              #{{ caseNumber }}
            </span>
          </b-col>
          <b-col
            cols="8"
            class="align-left"
          >
            <span
              v-if="item.draft && item.status !== 'CLOSED' && type === 'tasks'"
              class="footer-status badge-draft"
            >
              {{ $t('Draft') }}
            </span>
            <b-badge
              class="footer-status"
              :style="colorStatus"
            >
              {{ showBadge(item) }}
            </b-badge>
            <img
              v-if="item.is_priority"
              src="/img/priority.svg"
              class="mobile-priority"
              :alt="$t('Priority')"
            >
          </b-col>
        </b-row>
      </b-card-footer>
    </a>
  </b-card>
  <b-card v-else class="card-tasks">
    <div class="card-tasks-content">
      <span v-if="cardMessage === 'show-page'">Page {{ currentPage }} of {{ totalPages }}</span>
      <span v-if="cardMessage === 'show-more' && !loading"> {{ $t('Show More') }}</span>
      <span v-if="loading"><i class="fas fa-spinner fa-spin"></i> {{ $t('Loading') }}...</span>
    </div>
  </b-card>
</template>

<script>
import { cloneDeep } from "lodash";
import AvatarImage from "../components/AvatarImage.vue";

export default {
  components: { AvatarImage },
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
    fields: {
      type: Array,
      default: () => []
    },
  },
  data() {
    return {
      openURL: "",
      colorStatus: "",
      requestBadge: "",
      taskStatus: "",
      isVisible: false,
      title1: "",
      title2: "",
      formatItem: [],
      callbackResize: function() {}
    };
  },
  computed: {
    caseNumber() {
      if (this.type === "requests") {
        return this.item.case_number;
      }
      if (this.type === "tasks") {
        return this.item.process_request.id;
      }
      return null;
    },
  },
  watch: {
    item() {
      this.formatItem = cloneDeep(this.item);
      if (this.type === "requests") {
        this.splitText(this.sanitize(this.item.case_title_formatted));
      } else if (this.type === "tasks") {
        this.splitText(this.sanitize(this.item.process_request.case_title_formatted));
      }
      this.fields.forEach((row) => {
        if (row.field === "tasks") {
          this.formatItem[row.field] = this.formatActiveTasks(this.formatItem.active_tasks);
        }
        if (row.format && row.format === "dateTime") {
          this.formatItem[row.field] = this.formatDate(this.formatItem[row.field]);
        }
      });
    },
  },
  mounted() {
    this.formatItem = cloneDeep(this.item);
    this.callbackResize = () => {
      if (this.type === "requests") {
        this.splitText(this.sanitize(this.item.case_title_formatted));
      } else if (this.type === "tasks") {
        this.splitText(this.sanitize(this.item.process_request.case_title_formatted));
      }
    };
    window.addEventListener("resize", this.callbackResize);
    if (this.type === "tasks") {
      this.splitText(this.sanitize(this.item.process_request.case_title_formatted));
    } else if (this.type === "requests") {
      this.splitText(this.sanitize(this.item.case_title_formatted));
    }
    this.fields.forEach((row) => {
      if (row.field === "tasks") {
        this.formatItem[row.field] = this.formatActiveTasks(this.formatItem.active_tasks);
      }
      if (row.format && row.format === "dateTime") {
        this.formatItem[row.field] = this.formatDate(this.formatItem[row.field]);
      }
    });
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.callbackResize);
  },
  methods: {
    /**
     * Show info in the badge
     */
    showBadge() {
      const statusMap = {
        "DRAFT": { color: "#F9E8C3", label: this.$t("Draft") },
        "CANCELED": { color: "#FFC7C7", label: this.$t("Canceled") },
        "CLOSED": { color: "#B8DCF7", label: this.$t("Completed") },
        "COMPLETED": { color: "#B8DCF7", label: this.$t("Completed") },
        "ERROR": { color: "#FFC7C7", label: this.$t("Error") },
        "ACTIVE": {
          "overdue": { color: "#FFC7C7", label: this.$t("Overdue") },
          "open": { color: "#C8F0CF", label: this.$t("In Progress") },
          "default": { color: "#C8F0CF", label: this.$t("In Progress") }
        },
        "default": { color: "#C8F0CF", label: this.$t("In Progress") }
      };

      if (this.item.status === "ACTIVE") {
        const advanceStatus = this.item.advanceStatus ? statusMap["ACTIVE"][this.item.advanceStatus] : statusMap["ACTIVE"]["default"];
        this.colorStatus = `background-color: ${advanceStatus.color}`;
        this.requestBadge = advanceStatus.label;
      } else {
        const currentStatus = statusMap[this.item.status] || statusMap["default"];
        this.colorStatus = `background-color: ${currentStatus.color}`;
        this.requestBadge = currentStatus.label;
      }

      return this.requestBadge;
    },
    /**
     * Format the date
     */
    formatDate(value) {
      if (value) {
        return window.moment(value).format('DD MMM YYYY / HH:mm');
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
    splitText(text) {
      // Split the text into two lines
      this.title1 = "";
      this.title2 = "";
      let lineBreak = false;
      this.isVisible = true;
      const words = text.split(' ');
      this.$nextTick(() => {
        const fullText = this.$refs.fullText;
        const fullWidth = fullText.offsetWidth;
        words.forEach((word) => {
          if (((this.title1 + word).length < (fullWidth / 7)) && !lineBreak) {
            this.title1 += `${word} `;
          } else {
            this.title2 += `${word} `;
            lineBreak = true;
          }
        });
        this.isVisible = false;
      });
    },
    formatActiveTasks(value) {
      return value.map((task) => `${task.element_name}`).join(", ");
    },
    sanitize(html) {
      return this.removeScripts(html);
    },
    removeScripts(input) {
      const doc = new DOMParser().parseFromString(input, 'text/html');

      const scripts = doc.querySelectorAll('script');
      scripts.forEach((script) => {
        script.remove();
      });

      const styles = doc.querySelectorAll('style');
      styles.forEach((style) => {
        style.remove();
      });

      return doc.body.innerHTML;
    },
  },
};
</script>

<style scoped>
a {
  color: #4C545C;
}
.bodyInfo {
  color: rgba(76, 84, 92, 0.7);
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.titleInfo {
  color: #1572C2;
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
}
.footer-case-number {
  font-weight: 500;
  font-size: 13px;
}
.footer-status {
  color: rgba(0, 0, 0, 0.75);
  font-weight: 700;
  font-size: 12px;
  padding: 7px;
  border-radius: 4.5px;
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
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  margin-left: 1rem;
}

.card-tasks-content {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
.card-divider {
  border-top: 1px solid #CDDDEE;
}
.card-mobile {
  border: #CDDDEE 1px solid;
  border-radius: 8px;
}
.card-mobile-body {
  color: #4C545C;
  padding: 16px 16px 0 16px;
}
.card-mobile-footer {
  background-color: rgba(250, 250, 250, 1);
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}
.line-1 {
  font-size: 16px;
}

.line-2 {
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding-bottom: 10px;
}
.badge-draft {
  background-color: #F9E8C3;
  margin-right: 3px;
  line-height: 1;
  display: inline-block;
}
.mobile-priority {
  background-color: #F8E3E5;
  padding: 5px;
  border-radius: 4.5px;
}
</style>
