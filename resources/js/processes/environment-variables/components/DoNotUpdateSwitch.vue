<template>
  <div class="do-not-update-switch pt-3 mt-2 border-top">
    <div class="d-flex align-items-start">
      <b-form-checkbox
        class="mt-1"
        switch
        :checked="value"
        @input="$emit('input', $event)"
      />
      <div class="ml-2">
        <div class="font-weight-bold">
          <i class="fas fa-lock text-primary mr-1" aria-hidden="true"></i>
          {{ $t("Do not overwrite this environment variable on import/DevLink") }}
        </div>
        <small class="form-text text-muted mb-0">
          {{ $t("When enabled, the value of this variable will not be overwritten during imports or DevLink synchronization, even if a variable with the same name exists in the source environment.") }}
        </small>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
