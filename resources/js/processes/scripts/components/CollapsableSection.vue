<template>
  <div>
    <b-list-group-item class="bg-light">
      <b-row v-b-toggle="'accordion-' + title">
        <b-col>{{ title }}</b-col>
        <b-col align-self="end" cols="1">
          <i class="fas fa-chevron-down accordion-icon"/>
        </b-col>
      </b-row>
    </b-list-group-item>

    <b-collapse :id="'accordion-' + title">
      <b-list-group-item>
        <slot/>
      </b-list-group-item>
    </b-collapse>
  </div>
</template>

<script>
export default {
  props: ['title'],
}
</script>

<style scoped>
.accordion-icon {
  transition: all 200ms;
}

.collapsed .accordion-icon {
  transform: rotate(-90deg);
}
</style>
