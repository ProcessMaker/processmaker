<template>
  <b-col
    v-if="displayUser"
    lg="auto"
    class="pr-0"
  >
    <avatar-image
      size="40"
      hide-name="true"
      :input-data="user"
    />
  </b-col>
  <b-col
    v-else
    lg="auto"
    class="pr-0"
  >
    <div class="notification-icon rounded-circle d-flex justify-content-center">
      <i class="fas fa-bell align-self-center" />
    </div>
  </b-col>
</template>

<script>
import notificationsMixin from "../notifications-mixin";
import AvatarImage from "../../components/AvatarImage";

export default {
  components: { AvatarImage },
  mixins: [notificationsMixin],
  props: {
    notification: {
      type: Object,
      required: true,
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";
.notification-icon {
  background-color: $warning;
  width: 40px;
  height: 40px;

  i {
    color: white;
    font-size: 1.6em;
  }
}
</style>
