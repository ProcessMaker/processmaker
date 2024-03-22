<template>
  <b-popover :target="id"
             triggers="click"
             placement="bottomleft"
             boundary="window"
             custom-class="pm-popover-confirmation"
             @hidden="$emit('onHidden', $event)">
    <div class="row p-2">
      <div class="col-auto pm-popover-confirmation-text">
        <slot name="content-message"></slot>
      </div>
      <div class="col">
        <slot name="content-button"></slot>
      </div>
    </div>
  </b-popover>
</template>

<script>
  export default {
    props: {
      id: {
        type: String,
        default: ""
      }
    }
  };
</script>

<style scoped>
  .pm-popover-confirmation {
    max-width: 450px;
  }
  .pm-popover-confirmation-text {
    display: flex;
    align-items: center;
    justify-content: center;
  }
</style>