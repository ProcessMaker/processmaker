<template>
  <div>
    <div class="process-name-container">
      <li class="font-weight-bold sidebar-nav">
        <b-link
          @click="showProcessElements = !showProcessElements"
          :aria-expanded="showProcessElements ? 'true' : 'false'"
          class="sidebar-nav"
        >
          <div class="d-flex justify-content-between align-items-center p-2 active">
            <span>{{ processName }}</span>
            <i
              class="fa chevron"
              :class="{
                'fa-chevron-right': !showProcessElements,
                'fa-chevron-down': showProcessElements,
              }"
              style="width: 14px"
            />
          </div>
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
        <li class="nav-list-item">
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-code sidebar-nav" />
            <span>{{ $t("Scripts") }}</span>
          </b-link>
        </li>
        <li class="nav-list-item">
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-file-alt sidebar-nav" />
            <span>{{ $t("Screens") }}</span>
          </b-link>
        </li>
        <li class="nav-list-item">
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-lock sidebar-nav" />
            <span>{{ $t("Environment Variables") }}</span>
          </b-link>
        </li>
        <li class="nav-list-item">
          <b-link class="sidebar-nav">
            <i class="fas nav-icon bpmn-icon-end-event-signal" />
            <span>{{ $t("Signals") }}</span>
          </b-link>
        </li>
        <li class="nav-list-item">
          <b-link class="sidebar-nav">
            <i class="fas nav-icon fa-cog" />
            <span>{{ $t("Data Connectors") }}</span>
          </b-link>
        </li>
        <li class="nav-list-item">
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
    };
  },
  mounted() {
  },
  methods: {
  },

};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.process-name-container {
  text-align: left;
  width: 100%;
}

a.sidebar-nav {
  color: #42526E;
}

li {
  list-style: none;
  text-align: left;
  width: 100%;
  &:hover {
    background-color: $grey-bg;
  }
}

.nav-list-item {
  padding-top: 0.5em;
  padding-bottom: 0.5em;
  padding-left: 0.5em;
}

.active {
    background-color: $grey-bg;
    border-radius: 0.125em;
}

.active::after {
  position: absolute;
  left: 0.4em;
  width: 4px;
  height: 24px;
  content: "";
  background-color: #0872c2;
  border-radius: 6px;
}

</style>
