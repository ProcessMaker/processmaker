<template>
  <div class="container-lang">
    <label class="choose-lang m-2 text-uppercase">
      {{ $t("Choose a language") }}
    </label>
    <div
      v-for="(lang, index) in languages"
      :key="index"
    >
      <b-card
        :ref="`${lang}`"
        class="mt-2 card-lang"
        @click="selectLanguage(lang, index)"
      >
        <b-card-text>
          <img
            :src="getImage(lang)"
            :alt="lang"
          >
          <span class="text-uppercase ml-2">
            {{ lang }}
          </span>
        </b-card-text>
      </b-card>
    </div>
  </div>
</template>

<script>
export default {
  props: ["languages", "select"],
  methods: {
    /**
     * Check the language selected and emit to modal
     */
    selectLanguage(lang, index) {
      this.selectedLang(lang);
      this.select(index);
    },
    /**
     * Get the Icon of the language
     */
    getImage(lang) {
      return `/img/script_lang/${lang}.svg`;
    },
    /**
     * Add the border to the item selected
     */
    selectedLang(lang) {
      for (const item in this.$refs) {
        this.$refs[item][0].className = "card mb-2 card-lang";
      }
      this.$refs[lang][0].className = "card mb-2 card-lang selected";
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
    width: 90%;
    margin-left: 5%;
  }
  .container-lang {
    background-color: #F6F9FB;
    height: 100%;
    font-size: 14px;
  }
  .choose-lang {
    color: #6A7888;
  }
</style>
