<template>
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
          <!-- Process Map -->
          <template>
            <div>
              <processes-carousel :images="carouselImages" :defaultImage="defaultImage" />
            </div>
          </template>
        </b-col>
        <b-col cols="3"> Process Options </b-col>
      </div>
      <b-col cols="12"> Process Tab </b-col>
    </b-col>
  </b-row>
</template>

<script>
import ProcessesCarousel from "../components/ProcessesCarousel.vue";

export default {
  components: { ProcessesCarousel },
  data() {
    return {
      fields: [],
      carouselImages: [
        { url: "https://picsum.photos/1024/480/?image=55" },
        { url: "https://picsum.photos/1024/480/?image=54" },
      ],
      defaultImage: [
        { url: "/img/launchpad-images/noImage.svg" },
      ],
    };
  },
  methods: {
    /**
     * TO DO: This method gets information related to processes Launchpad Settings
     */
    getLaunchpadSettings() {
      //Here call API to retrieve information
      // ProcessMaker.apiClient
      //   .get("query here")
      //   .then((response) => {
      //     this.carouselImages = response.data;
      //   })
      //   .catch((err) => {
      //     console.log("Error", err);
      //   });
    },
  },
};
</script>
