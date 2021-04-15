<template>
  <div class="setting-text">
    <div v-if="input === null || !input.length" class="font-italic text-black-50">
      Empty
    </div>
    <div v-else>
      <template v-if="! ui('sensitive')">
        {{ input }}
      </template>
      <template v-else>
        {{ hidden }}
      </template>
    </div>
    <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden" @shown="onModalShown">
      <template v-slot:modal-header class="d-block">
        <div>
          <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
          <h5 class="mb-0" v-else>{{ setting.key }}</h5>
          <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
        </div>
        <button type="button" aria-label="Close" class="close" @click="onCancel">×</button>
      </template>
      <template v-if="! ui('sensitive')">
        <b-form-input ref="input" v-model="transformed" @keyup.enter="onSave" spellcheck="false" autocomplete="off" type="text"></b-form-input>
      </template>
      <template v-else>
        <b-input-group>
          <b-form-input class="border-right-0" ref="input" v-model="transformed" @keyup.enter="onSave" spellcheck="false" autocomplete="new-password" :type="type"></b-form-input>
          <b-input-group-append>
            <b-button variant="secondary" @click="togglePassword">
              <i class="fas" :class="icon"></i>
            </b-button>
          </b-input-group-append>
        </b-input-group>
      </template>
      <div slot="modal-footer" class="w-100 m-0 d-flex">
        <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
            {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-secondary ml-3" @click="onSave" :disabled="! changed">
            {{ $t('Save')}}
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ['value', 'setting'],
  data() {
    return {
      input: null,
      showModal: false,
      transformed: null,
      type: 'password'
    };
  },
  computed: {
    variant() {
      if (this.disabled) {
        return 'secondary';
      } else {
        return 'success';
      }
    },
    changed() {
      return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    },
    icon() {
      if (this.type == 'password') {
        return 'fa-eye';
      } else {
        return 'fa-eye-slash';
      }
    },
    hidden() {
      return '•'.repeat(this.input.length);
    }
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    }
  },
  methods: {
    onCancel() {
      this.showModal = false;
    },
    onEdit() {
      this.showModal = true;
    },
    onModalHidden() {
      this.type = 'password';
      this.transformed = this.copy(this.input);
    },
    onModalShown() {
      this.$refs.input.focus();
    },
    onSave() {
      this.input = this.copy(this.transformed);
      this.showModal = false;
      this.emitSaved(this.input);
    },
    togglePassword() {
      if (this.type == 'text') {
        this.type = 'password';
      } else {
        this.type = 'text';
      }
      this.$refs.input.focus();
    }
  },
  mounted() {
    if (this.value === null) {
      this.input = '';
    } else {
      this.input = this.value;
    }
    this.transformed = this.copy(this.input);
  }
};
</script>

<style lang="scss" scoped>
  @import '../../../../sass/colors';

  $disabledBackground: lighten($secondary, 20%);

  .btn:disabled,
  .btn.disabled {
    background: $disabledBackground;
    border-color: $disabledBackground;
    opacity: 1 !important;
  }
</style>
