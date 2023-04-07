<template>
  <div>
    <b-tabs fill lazy>
      <b-tab title="JSON View">
        <pre id="dataJson">{{ dataJson }}</pre>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
export default {
  name: "type-view",
  props: ["dataJson"],
  data() {
    return {};
  },
};
</script>

<style scoped>
#dataJson {
  text-align: justify;
  width: auto;
  height: 300px;
  background: #efefef;
}
</style>
