<template>
    <div>
      <!-- <b-button :aria-label="$t('Create Process')" v-b-modal.templateDetails class="mb-3 mb-md-0 ml-md-2">
        <i class="fas fa-plus"/> {{ $t('Process') }}
      </b-button> -->
      <modal
        id="templateDetails"
        size="huge"
        :title="$t('[[Template Name]]')"
        :hide-footer="true"
        @ok.prevent="onSubmit"
      >
      <div>
        <b-button :aria-label="$t('Use Template')" class="mb-3 blank-template-btn">
          {{ $t('Use Template') }}
        </b-button>
      </div>
        <p class="text-muted">{{ $t("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.") }}</p>
        <div>
          <p class="text-muted">{{ $t("Created By:") }}
            <avatar-image
                    size="25"
                    hide-name="false"
            ></avatar-image>
          </p>
        <b-badge pill variant="success" class="category-badge mb-3">
          Category 1
        </b-badge>
        </div>
        <img src="https://via.placeholder.com/429x478">
      </modal>
    </div>
</template>

<script>
import { Modal } from "SharedComponents";
import AvatarImage from '../AvatarImage.vue';

  export default {
    components: { Modal, AvatarImage },
    mixins: [ ],
    props: ['template'],
    data: function() {
      return {
      }
    },
    methods: {},
    mounted() {
      console.log('TEMPLATE DETAILS MOUNTED', this.template);
    }
  };
</script>

<style scoped>
  .blank-template-btn {
    float: right;
    background-color: #1572C2;
  }

  .category-badge {
    background-color: #DEEBFF;
    color: #104A75;
    font-size: 12px;
    border: 1px solid #104A75;
  }
</style>
