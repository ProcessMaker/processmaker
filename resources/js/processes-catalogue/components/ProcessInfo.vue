<template>
  <div>
    <breadcrumbs />
    <b-row>
      <b-col cols="2">
        <h4>{{ $t("Processes Browser") }}</h4>
        <!--
        Menu Catalogue FOUR-12111
        <MenuCatologue
          :data="listCategories"
          :select="selectCategorie"
          class="mt-3"
        />
        -->
      </b-col>
      <b-col cols="10">
        <div class="d-flex">
          <b-col cols="9">
            <process-map
              :process="process"
              :permission="permission"
              :current-user-id="currentUserId"
              :is-documenter-installed="isDocumenterInstalled"
            />
            <processes-carousel
              :process="process"
            />
          </b-col>
          <b-col cols="3">
            <process-options :process="process" />
          </b-col>
        </div>
        <b-col cols="12">
          <process-tab></process-tab>
        </b-col>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import ProcessesCarousel from "../components/ProcessesCarousel.vue";
import ProcessMap from "./ProcessMap.vue";
import ProcessOptions from "./ProcessOptions.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import ProcessTab from './ProcessTab.vue';

export default {
  components: { ProcessOptions, Breadcrumbs, ProcessMap, ProcessesCarousel, ProcessTab },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
  data() {
    return {
      fields: [],
    };
  },
};
</script>
