<template>
  <div class="py-3 d-flex">
    <div
      v-if="icon"
      :class="iconClass || 'timeline-icon'"
      class="badge timeline-badge mr-1 rounded d-flex justify-content-center align-items-center"
    >
      <slot
        v-if="hasIconSlot"
        name="icon"
      />
      <i
        v-else
        :class="icon"
      />
    </div>
    <avatar-image
      v-if="value.user"
      :input-data="value.user"
      class="mr-1"
      hide-name="true"
      size="24"
    />
    <img
      v-else
      class="default-user mr-1"
      src="/img/systemAvatar.png"
    >
    <div class="flex-grow-1">
      <strong :title="value.updated_at">{{
        moment(value.updated_at).format()
      }}</strong>
      <slot
        v-if="hasBodySlot"
        name="body"
      />
      <template v-else>
        {{ value.body }}
      </template>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: Object,
    iconClass: String,
    icon: String,
  },
  computed: {
    hasIconSlot() {
      return !!this.$slots.icon;
    },
    hasBodySlot() {
      return !!this.$slots.body;
    },
  },
};
</script>

<style scoped>
.default-user {
  max-height: 24px;
  max-width: 24px;
  border-radius: 50%;
}
</style>
