<template>
  <div>
    <b-modal id="set-password-modal">
      <div class="card-header bg-light pt-0">
        <h5>{{ $t("Set Password") }}</h5>
        <p class="text-muted pt-0">{{ $t("This password will be required when importing the exported package/process.") }}</p>
      </div>
    </b-modal>
  </div>
</template>

<script>
export default {
  props: [],
    components: {
    },
    mixins: [],
    data() {
        return {

        }
    },
    methods: {      
    },
    mounted() {
    }

}
</script>

<style>

</style>