<template>
  <div class="pb-2 template-select-card-container">
    <wizard-template-card
      v-if="type === 'wizard'"
      :template="template"
      @showDetails="showDetails()" 
      @mouseenter="addHoverClass"
      @mouseleave="removeHoverClass"
    />
    <default-template-card
      v-else
      :template="template"
      @showDetails="showDetails()" 
      @mouseenter="addHoverClass"
      @mouseleave="removeHoverClass"
    />
    
    
    <!-- <b-card
      no-body
      class="template-select-card"
      :class=" type === 'wizard' ? 'wizard-card p-0' : 'p-1'"
      :style="{backgroundImage: 'url(' + template?.backgroundImage + ')'}"
      @click="showDetails()"
      @mouseenter="addHoverClass"
      @mouseleave="removeHoverClass"
    >
    
      <b-card-body v-if="type === 'wizard'">
        <div class="wizard-icon-container text-right mb-3">
          <img src="../../../img/wizard-template-icon.svg" alt="Wizard Icon">
        </div>
        <b-card-text class="mb-2">
          <img :src="template.icon" :alt="template.name + 'icon'" width="30px"/>
          <h5>{{ template.name  | str_limit(30) }}</h5>
          {{ template.shortDescription | str_limit(150) }}
        </b-card-text>
      </b-card-body>

      <b-card-body v-else
        :title="template.name | str_limit(30)"
        class="card-body"
      >
        <b-card-text class="mb-2">
          {{ template.description | str_limit(150) }}
        </b-card-text>
        <b-badge
          v-for="category in categories"
          :key="category.id"
          pill
          class="category-badge mb-3 mr-1"
        >
          {{ category.name }}
        </b-badge>
        <small
          v-if="template.categories.length > 3"
          class="text-muted"
        >+{{ catCount }}</small>
      </b-card-body>
    </b-card> -->
  </div>
</template>

<script>
import Vue from "vue";
import WizardTemplateCard from './WizardTemplateCard';
import DefaultTemplateCard from './DefaultTemplateCard';

Vue.filter("str_limit", (value, size) => {
  if (!value) return "";
  value = value.toString();

  if (value.length <= size) {
    return value;
  }
  return `${value.substr(0, size)}...`;
});

export default {
  components: {WizardTemplateCard, DefaultTemplateCard},
  props: ["template", "type"],
  data() {
    return {
      thumbnail: null,
      catLimit: 3,
    };
  },
  // computed: {
  //   categories() {
  //     return this.catLimit ? this.template.categories.slice(0, this.catLimit) : this.template.categories;
  //   },
  //   catCount() {
  //     const { length } = this.template.categories;
  //     return length - this.catLimit;
  //   },
  // },
  watch: {},
  beforeMount() {},
  mounted() {
  },
  methods: {
    showDetails() {
      this.$emit("show-details", { template: this.template });
    },
    addHoverClass(event) {
      event.target.classList.add("hover");
    },
    removeHoverClass(event) {
      event.target.classList.remove("hover");
    },
  },
};
</script>

<style lang="scss" scoped>
.template-select-card-container {
  flex: 0 0 33.333333%;
}
.template-select-card {
  // width: 292px;
  height: 172px;
  border-radius: 4px;
  padding: 10px 8px 10px 8px;
  overflow: hidden;
  border: 2px solid rgba(0, 0, 0, 0.125);
}

.card-title {
  font-weight: 600;
  font-size: 14px;
}

.card-img {
  background: #80808017;
  height: 112px;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2px!important;
}

.card-text {
  font-size: 12px;
  color: #6C757D;
}

.category-badge {
  background-color: #DEEBFF;
  color: #104A75;
  font-size: 12px;
}

.wizard-card {
  border-radius: 16px;
  border: 1px solid #CDDDEE;
  background-size: cover;
  height: 243px;
  .card-body {
    padding: 10px !important;
  }
}

.wizard-card:hover {
  box-shadow: 0px 10px 20px 4px #00000021;
}

.hover {
  border-color: #1572C2;
  cursor: pointer;
}

@media (min-width: 576px) {
  .card-deck .card {
    margin-left: 9px;
    margin-right: 9px;
  }
}

</style>
