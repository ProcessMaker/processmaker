<template>
  <div>
    <div class="d-flex justify-content-center my-5">
      <img
        class="image d-flex"
        src="/img/processes-catalogue-empty.svg"
        alt="recent projects"
      >
    </div>
    <h4 class="text-center">
      {{ $t("Currently, you don't have any processes created.") }}
    </h4>
    <p class="text-center">
      {{ $t('We encourage you to create new processes using our templates.') }}
    </p>
    <p class="text-center my-4">
      <button
        type="button"
        class="btn btn-primary text-capitalize"
      >
        {{ $t("Show Me The Templates") }}
      </button>
    </p>
  </div>
</template>
