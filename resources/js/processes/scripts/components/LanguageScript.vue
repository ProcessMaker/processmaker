<template>
  <div class="container-lang"
       :class="{'is-invalid': invalid_feedback !== ''}"
  >
    <label class="choose-lang m-2 text-uppercase">
      {{ $t("Choose an Executor") }}
    </label>
    <div class="content-lang">
      <template
        v-for="(lang, index) in languages"
      >
        <b-card
          :key="index"
          :ref="`${lang.title}-${index}`"
          class="mt-2"
          @click="selectLanguage(lang, index)"
        >
          <b-card-text class="row">
            <b-col cols="2">
              <img
                :src="getImage(lang)"
                :alt="lang.title"
              >
            </b-col>
            <b-col
              class="d-flex flex-column"
              cols="10"
            >
              <span class="text-uppercase">
                {{ lang.language }}
              </span>
              <span class="text-title text-muted font-italic">
                {{ lang.title }}
              </span>
            </b-col>
          </b-card-text>
        </b-card>
      </template>
      <span
        v-if="showError !== ''"
        class="d-block invalid-feedback"
      >
        {{ invalid_feedback }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  props: ["languages", "select", "invalid_feedback"],
  data() {
    return {
      showError: false,
    };
  },
  watch: {
    invalid_feedback(newVal) {
      this.showError = newVal === "";
    },
  },
  methods: {
    /**
     * Check the language selected and emit to modal
     */
    selectLanguage(lang, index) {
      this.selectedLang(lang, index);
      this.select(index);
    },
    /**
     * Get the Icon of the language
     */
    getImage(lang) {
      let srcImage = "";
      switch (lang.language) {
        case "php":
        case "javascript":
        case "lua":
          srcImage = `/img/script_lang/${lang.language}.svg`;
          break;
        default:
          srcImage = "/img/script_lang/default.svg";
      }
      return srcImage;
    },
    /**
     * Add the border to the item selected
     */
    selectedLang(lang, index) {
      for (const item in this.$refs) {
        this.$refs[item][0].className = "card mb-2 card-lang";
      }
      this.$refs[lang.title + '-' + index][0].className = "card mb-2 card-lang selected";
    },
  },
};
</script>

<style scoped>
  .selected {
    border: 3px solid #1572C2;
    background-color: #EDF6FF;
  }
  .card-lang {
    cursor: pointer;
  }
  .container-lang {
    background-color: #F6F9FB;
    height: 100%;
    max-height: 450px;
    font-size: 14px;
    overflow-y: auto;
  }
  .container-lang.is-invalid {
    border: 1px solid #E50130;
  }
  .choose-lang {
    color: #6A7888;
  }
  .content-lang {
    width: 90%;
    margin-left: 5%;
  }
  .card-body {
    padding: 12px;
  }
  .text-title {
    font-size: 12px;
  }
</style>
