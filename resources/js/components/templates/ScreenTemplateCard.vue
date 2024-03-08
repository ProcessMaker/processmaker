<template>
  <div>
    <b-card
      no-body
      bg-variant="transparent"
      class="screen-template-card pt-2"
    >
      <b-card-body>
        <img src="https://picsum.photos/200/100" class="card-image">
        {{ template.name }}
      </b-card-body>
    </b-card>
  </div>
</template>

<script>
import templateMixin from './mixins/template.js';

export default {
  mixins: [templateMixin],
  props: ["template"],
  data() {

  }
}
</script>

<style lang="scss" scoped>

.screen-template-card {
  border: none;
}
.card-image{
  border-radius: 6px;
}
</style>