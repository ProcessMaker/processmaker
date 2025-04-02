<template>
    <b-modal
      ref="createDevLinkModal"
      id="create"
      centered
      size="lg"
      :title="$t('Create new DevLink')"
      @hidden="clear"
      @ok="create"
      :ok-title="$t('Create')"
      :cancel-title="$t('Cancel')"
    >
      <div v-if="status === 'error'" class="alert alert-danger" role="alert">
        <div class="alert-header">
          <span class="icon">!</span>
          <strong>Connection Error</strong>
        </div>
        <a href="#" @click.prevent="toggleDetails">{{ showDetails ? 'See Less Details' : 'See More Details' }}</a>
        <ul v-show="showDetails">
          <li>Please check your internet connection.</li>
          <li>Verify if the server is available or try again later.</li>
          <li>If the issue persists, contact support for assistance.</li>
        </ul>
      </div>
      <div>
        <b-form-group :label="$t('Please assign a name to the desired linked instance')">
          <b-form-input v-model="newName" :readonly="status === 'error'" />
        </b-form-group>
        <p>{{ $t('We require the access token of the instance you want to connect. If the token is correct, you will have to log-in in the corresponding instance.') }}</p>
        <b-form-group
          :label="$t('Instance URL')"
          :invalid-feedback="$t('Invalid URL')"
          :state="urlIsValid"
        >
          <b-form-input v-model="newUrl"></b-form-input>
        </b-form-group>
      </div>
    </b-modal>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  newName: {
    type: String,
    required: true
  },
  newUrl: {
    type: String,
    required: true
  },
  urlIsValid: {
    type: Boolean,
    required: true
  },
  status: {
    type: String,
    default: ''
  },
  showDetails: {
    type: Boolean,
    default: false
  }
});
const createDevLinkModal = ref(null);

const newName = ref(props.newName);
const newUrl = ref(props.newUrl);
const emit = defineEmits(['clear', 'create', 'update:newUrl']);

const showDetails = ref(false);

const clear = () => {
  newName.value = '';
  newUrl.value = '';
};

const create = (e) => {
  e.preventDefault();
  if (props.urlIsValid) {
    emit('create', newName.value, newUrl.value);
    if (props.status === 'success') {
      hide();
    }
  }
};

const show = () => {
  if (createDevLinkModal.value) {
    createDevLinkModal.value.show();
  }
};

const hide = () => {
  if (createDevLinkModal.value) {
    createDevLinkModal.value.hide();
  }
};

const toggleDetails = () => {
  showDetails.value = !showDetails.value;
};

watch(newUrl, (newValue) => {
  emit('update:newUrl', newValue);
});

defineExpose({
  show,
  hide,
});
</script>

<style lang="scss" scoped>
  @import "styles/components/modal";

  .alert {
    background-color: #FDF2F2;
    border: 1px solid #FBD0D0;
    color: #596372;
    padding: 20px;
    border-radius: 8px;
    position: relative;
    margin: 20px auto;
    font-size: 14px;
  }

  .alert-header {
    display: flex;
    align-items: center;
  }

  .alert .icon {
    font-weight: bold;
    color: #EC5962;
    border: 1px solid #EC5962;
    border-radius: 50%;
    margin-right: 10px;
    padding: 1px 7px;
    font-size: 10px;
  }

  .alert strong {
    margin-bottom: 0;
  }

  .alert ul {
    margin: 10px 0 0 0;
    padding-left: 20px;
    list-style-type: disc;
  }

  .alert a {
    color: #007bff;
    cursor: pointer;
    text-decoration: none;
  }

  .alert a:hover {
    text-decoration: underline;
  }
</style>
