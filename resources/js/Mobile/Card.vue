<template>
  <b-card class="m-3 card-mobile" no-body>
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
            <template v-if="type === 'tasks'">
              <b-link href="">
                #{{ item.process_request.id }}
                {{ item.process.name }}
              </b-link>
            </template>
            <template v-if="type === 'requests'">
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
            />
            <span class="footer-case-number">
              #{{ item.case_number }}
            </span>
          </b-col>
          <b-col
            v-if="type === 'requests'"
            cols="4"
            class="align-left"
          >
            <b-badge
              class="footer-status"
              :style="colorStatus"
            >
              {{ showBadge(item) }}
            </b-badge>
          </b-col>
        </b-row>
      </b-card-footer>
    </a>
  </b-card>
</template>

<script>
import { cloneDeep } from "lodash";
import AvatarImage from "../components/AvatarImage.vue";

export default {
  components: { AvatarImage },
  props: ["type", "item", "fields"],
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
    };
  },
  mounted() {
    this.formatItem = cloneDeep(this.item);
    window.addEventListener("resize", () => {
      console.log("resize");
      this.splitText(this.sanitize(this.item.case_title_formatted));
    });
    if (this.type === "tasks") {
      this.splitText(this.sanitize(this.item.process_request.case_title_formatted));
    } else if (this.type === "requests") {
      this.splitText(this.sanitize(this.item.case_title_formatted));
      this.fields.forEach((row) => {
        if (row.field === "tasks") {
          this.formatItem[row.field] = this.formatActiveTasks(this.formatItem.active_tasks);
        }
        if (row.format && row.format === "dateTime") {
          this.formatItem[row.field] = this.formatDate(this.formatItem[row.field]);
        }
      });
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize");
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
          this.colorStatus = "background-color: #F9E8C3";
          this.requestBadge = "Draft";
          break;
        case "CANCELED":
          this.colorStatus = "background-color: #FFC7C7";
          this.requestBadge = "Canceled";
          break;
        case "COMPLETED":
          this.colorStatus = "background-color: #B8DCF7";
          this.requestBadge = "Completed";
          break;
        case "ERROR":
          this.colorStatus = "background-color: #FFC7C7";
          this.requestBadge = "Error";
          break;
        default:
          this.colorStatus = "background-color: #C8F0CF";
          this.requestBadge = "In Progress";
          break;
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
      this.isVisible = true;
      const words = text.split(' ');
      this.$nextTick(() => {
        const fullText = this.$refs.fullText;
        const fullWidth = fullText.offsetWidth;
        words.forEach((word) => {
          if ((this.title1 + word).length < (fullWidth / 7)) {
            this.title1 += `${word} `;
          } else {
            this.title2 += `${word} `;
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
</style>
