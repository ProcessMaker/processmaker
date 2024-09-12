<template>
  <div class="t-relative" >
    <slot
      v-bind="{
        toogleShow,
        data
      }"
      
      name="input">
      <button
        @click.prevent.stop="show = !show"
        class="t-flex t-w-full t-justify-between t-items-center t-py-2 t-px-3 t-ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-400">
        <span>{{ data?.label || data?.value }} </span>

        <i class="fas fa-chevron-down" />
      </button>
    </slot>

    <transition :name="animation">
      <div
        v-if="show"
        :class="'bg-'+color+'-500'"
        class="block mt-1 rounded absolute z-10 shadow-lg w-full bg-white">
        <ul class="list-none overflow-hidden rounded">
          <li
            v-for="(option, index ) in optionsModel"
            :key="index"
            class="hover:bg-gray-200"
            :class="{
              'bg-gray-300': option?.value === data?.value
            }"
            @click.prevent.stop="onClick(option, index)">
            <slot
              name="option"
              v-bind="{
                option
              }">
              <span
                class="flex py-2 px-4 transition duration-300"
                :class="'theme-'+color">{{ option.label || option.value }}</span>
            </slot>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<script>
import {
  defineComponent, ref, computed, onMounted, onUnmounted, nextTick,
} from 'vue';

export default defineComponent({
  props: {
    color: {
      type: String,
      default: () => 'blue',
    },
    animation: {
      type: String,
      default: () => 'fade',
    },
    // Example input options {"label":"Label 1" , "value": "1"}
    options: {
      type: Array,
      default: () => [],
    },
    modelValue: {
      type: Object,
      default: () => null,
    },
  },
  emits: ['update:modelValue', 'change'],
  setup(props, { emit }) {
    const show = ref(false);
    const count = ref(0);

    const data = computed({
      get() {
        return props.modelValue;
      },
      set(value) {
        emit('update:modelValue', value);
      },
    });

    const optionsModel = computed({
      get() {
        return props.options;
      },
      set(val) {
        emit('update:options', val);
      },
    });

    const onClick = (option, index) => {
      show.value = false;
      data.value = option;


      emit('change', option);
    };

    const addBodyListener = () => {
      show.value = false;
    };

    const toogleShow = () => {
      show.value = !show.value;
    };

    onMounted(() => {
      document.body.addEventListener('click', addBodyListener);
    });

    onUnmounted(() => {
      document.body.removeEventListener('click', addBodyListener);
    });

    return {
      show,
      data,
      optionsModel,
      count,
      onClick,
      toogleShow,
    };
  },
});
</script>

<style scoped>
/* Animations */
.fade-enter-active, .fade-leave-active {
  transition: opacity .1s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}

/* Slide-in-up animation*/
.slide-in-up-enter-active, .slide-in-up-leave-active {
  transition: all .5s;
  transform: translateY(0);
}
.slide-in-up-enter, .slide-in-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Slide-in-right animation*/
.slide-in-right-enter-active, .slide-in-right-leave-active {
  transition: all .5s;
  transform: translateX(0);
}
.slide-in-right-enter, .slide-in-right-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Slide-in-left animation*/
.slide-in-left-enter-active, .slide-in-left-leave-active {
  transition: all .5s;
  transform: translateX(0);
}
.slide-in-left-enter, .slide-in-left-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

/* Scale animation*/
.scale-enter-active, .scale-leave-active {
  transition: all .5s;
  transform: scale(1);
}
.scale-enter, .scale-leave-to {
  opacity: 0;
  transform: scale(0);
}

/* Rotate animation*/
.rotate-enter-active, .rotate-leave-active {
  transition: all .5s;
  transform: scale(1) rotate(-360deg);
}
.rotate-enter, .rotate-leave-to {
  opacity: 0;
  transform: scale(0) rotate(360deg);
}
</style>
