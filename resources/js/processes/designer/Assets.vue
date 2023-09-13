<template>
  <div class="assets p-3">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("Assets") }}
      </b-navbar-brand>
    </b-navbar>
    <div class="mt-3">
      <b-card-group deck>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#4DA2EB"
            icon="fas fa-play-circle"
            asset_name="Processes"
          />
        </b-card>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#8EB86F"
            icon="fas fa-file-alt"
            asset_name="Screens"
          />
        </b-card>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#F7CF5D"
            icon="fas fa-code"
            asset_name="Scripts"
          />
        </b-card>
      </b-card-group>
    </div>
    <div class="mt-3">
      <b-card-group deck>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#712F4A"
            icon="fas fa-table"
            asset_name="Decision Tables"
          />
        </b-card>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#D66A5F"
            icon="fas fa-database"
            asset_name="Collections"
          />
        </b-card>
        <b-card
          bg-variant="light"
          class="text-center"
        >
          <asset
            color="#B5D3E7"
            icon="fas fa-share-alt"
            asset_name="Data Connectors"
          />
        </b-card>
      </b-card-group>
    </div>
  </div>
</template>

<script>
import Asset from "./Asset.vue";

export default {
  components: { Asset },
  data() {
    return { };
  },
};
</script>

<style scoped>
.assets {
  background-color: #F9F9F9;
}
.card {
  border-radius: 8px;
}
</style>
