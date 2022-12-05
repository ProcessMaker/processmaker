<template>
  <div>
    <div class="process-name">
      <li class="font-weight-bold active sidebar-nav">
        <b-link
          @click="showProcessElements = !showProcessElements"
          :aria-expanded="showProcessElements ? 'true' : 'false'"
          class="sidebar-nav"
        >
          <i
            class="fa"
            :class="{
              'fa-chevron-right': !showProcessElements,
              'fa-chevron-down': showProcessElements,
            }"
            style="width: 14px"
          />
          <span>{{ processName }}</span>
        </b-link>
      </li>
    </div>
    <div>
      <!-- <pre
                class="mt-1 mb-0"
                @click="
                showProcessElements = !showProcessElements
                ">{{ initDockerfile.split("\n")[0] }} <template v-if="!showDockerfile">...</template></pre> -->
      <b-collapse id="processelements" v-model="showProcessElements" :aria-hidden="showProcessElements ? 'false' : 'true'">
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-code sidebar-nav" />
            <span>{{ $t("Scripts") }}</span>
          </b-link>
        </li>
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-file-alt sidebar-nav" />
            <span>{{ $t("Screens") }}</span>
          </b-link>
        </li>
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-lock sidebar-nav" />
            <span>{{ $t("Environment Variables") }}</span>
          </b-link>
        </li>
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon bpmn-icon-end-event-signal" />
            <span>{{ $t("Signals") }}</span>
          </b-link>
        </li>
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-cog" />
            <span>{{ $t("Data Connectors") }}</span>
          </b-link>
        </li>
        <li>
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-book" />
            <span>{{ $t("Vocabularies") }}</span>
          </b-link>
        </li>
      </b-collapse>
    </div>
  </div>
</template>

<script>
export default {
  components: {},
  props: ["processName"],
  data() {
    return {
      showProcessElements: false,
    }
  },
  mounted() {
  },
  methods: {
  },

};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";
@import "../../../sass/variables";

.process-name {
  text-align: left;
}

a.sidebar-nav {
  color: #42526E;
}

li {
  list-style: none;
  text-align: left;
  padding-top: 1em;
  &.active {
     background-color: $grey-bg;
     border-radius: 0.125em;
  }
}

</style>
