<template>
  <div class="mt-3">
    <b-tabs content-class="mt-3 text-style">
      <b-tab
        title="My Requests"
        active
      >
        <default-tab
          :altText="$t('No Image')"
          :titleText="$t('You have made no requests of this process.')"
          :descriptionText="$t('All your requests will be shown here')"
        />
      </b-tab>
      <b-tab title="My Tasks">
        <default-tab
          :altText="$t('No Image')"
          :titleText="$t('You have no tasks from this process')"
          :descriptionText="
            $t('All your tasks related to this process will be shown here.')
          "
        />
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import DefaultTab from "./DefaultTab.vue";
export default {
  components: { DefaultTab },
};
</script>
<style>
.text-style {
  margin-bottom: 10px;
  color: #556271;
}
</style>
