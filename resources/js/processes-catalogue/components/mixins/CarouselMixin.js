export default {
  methods: {
    /**
     * Get images from Media library related to process.
     */
    getLaunchpadImages() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.process.id}`)
        .then((response) => {
          const firstResponse = response.data.shift();
          const mediaArray = firstResponse.media;
          const embedArray = firstResponse.embed;
          mediaArray.forEach((media) => {
            const mediaType = media.custom_properties.type ?? "image";
            this.images.push({
              url: media.original_url,
              type: mediaType,
            });
          });
          embedArray.forEach((embed) => {
            const customProperties = JSON.parse(embed.custom_properties);
            this.images.push({
              url: customProperties.url,
              type: customProperties.type,
            });
          });
          // If no images were loaded Carousel container is not shown
          if (this.images.length === 0) {
            this.imagesLoaded = false;
          }
          // If only one image is loaded, rest of carousel must be completed with default image
          if (this.images.length === 1) {
            for (let i = 1; i <= 3; i += 1) {
              this.images[i] = {
                url: "/img/launchpad-images/defaultImage.svg",
                type: "image",
              };
            }
          }
        })
        .catch((error) => {
          console.error(error);
        });
    },
  },
};
