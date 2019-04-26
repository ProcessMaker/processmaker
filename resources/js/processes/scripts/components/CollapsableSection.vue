<template>
  <b-card no-body>
    <b-card-header>
      <b-row v-b-toggle="'accordion-' + title">
        <b-col>{{title}}</b-col>
        <b-col align-self="end" cols="1">
          <i class="fas fa-chevron-down accordion-icon"/>
        </b-col>
      </b-row>
    </b-card-header>

    <b-collapse :id="'accordion-' + title">
      <b-card-body>
        <slot/>
      </b-card-body>
    </b-collapse>
  </b-card>
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
