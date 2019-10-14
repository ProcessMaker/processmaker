<template>
    <div>
        <div :class="{'has-error':error}">
            <form-checkbox
                :label="$t('Enable Interstitial')"
                :checked="allowInterstitialGetter"
                @change="allowInterstitialSetter">
            </form-checkbox>
            <small v-if="error" class="text-danger">{{ error }}</small>
            <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
        </div>
        <screen-select
            v-if="allowInterstitialGetter"
            :label="$t('Screen Interstitial')"
            :helper="$t('What Screen Should Be Used For Rendering This Interstitial')"
            :params="parameters"
            v-model="screen"
        >

        </screen-select>

    </div>
</template>

<script>

  import ScreenSelect from "./ScreenSelect";
  export default {
    components: {ScreenSelect},
    props: ["value", "label", "helper"],
    data () {
      return {
        screen: null,
        loading: false,
        error: '',
        parameters: {
          type: 'DISPLAY'
        }
      };
    },
    computed: {
      /**
       * Get the value of the edited property
       */
      allowInterstitialGetter () {
        const node = this.node;
        console.log('allowInterstitialGetter');

        const value = _.get(node, "allowInterstitial");

        console.log(value);
        this.screen = _.get(node, "interstitialScreenRef");
        return value;
      },

      node () {
        console.log('node....................');
        console.log(this.$parent.$parent.$parent.$parent.highlightedNode.definition);
        return this.$parent.$parent.$parent.$parent.highlightedNode.definition;
      }
    },
    watch: {
      value: {
        immediate: true,
        handler(value) {
          console.log('handler value');
          console.log(value);
        }
      },
      screen: {
        handler(value) {
          console.log('handler interstitialScreenRef');
          console.log(value);
          this.$set(this.node, "interstitialScreenRef", value);
        }
      }
    },
    methods: {
      /**
       * Update allowInterstitial property
       */
      allowInterstitialSetter (value) {
        console.log('allowInterstitialSetter');
        console.log(value);
        this.$set(this.node, "allowInterstitial", value);

      },
    }
  };
</script>
