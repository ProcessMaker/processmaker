<template>
  <b-card
    header-class="card-size-header px-4 px-xl-5 d-flex d-md-none d-lg-flex align-items-center justify-content-center border-0"
    text-variant="white"
    class="flex-row card-border border-0"
    :class="cardClass"
  >
    <i slot="header" class="fas fa-2x" :class="iconClass"></i>
    <a :href="link" class="card-link text-light">
      <h1 v-if="showCount" class="m-0 font-weight-bold">{{ currentCount }}</h1>
      <h6 class="card-text">{{ $t(title) }}</h6>
    </a>
  </b-card>
</template>

<script>
  export default {
    data () {
      return {
        currentCount: null,
        loaded: false,
      }
    },
    props: {
      color: {
        type: String,
        default: 'secondary',
      },
      count: {
        type: Number,
        default: null,
      },
      countId: {
        default: null,
      },
      icon: {
        type: String,
        default: 'chart-line',
      },
      link: {
        type: String,
        default: '/',
      },
      title: {
        type: String,
        default: 'Count',
      },
      url: {
        type: String,
        default: null,
      }
    },
    mounted() {
      this.loadCount();
      
      if (this.countId !== null) {
        ProcessMaker.EventBus.$on('sidebar-count-updated-' + this.countId, count => {
          this.currentCount = count;
        });
      }
    },
    computed: {
      cardClass() {
        let classes = [];
        if (this.loaded) {
          classes.push('d-flex');
        } else {
          classes.push('d-none');
        }
        classes.push('bg-' + this.color)
        return classes.join(' ');
      },
      iconClass() {
        return 'fa-' + this.icon;
      },
      isInGroup() {
        return this.$parent.$options._componentTag == 'counter-card-group';
      },
      showCount() {
        return this.currentCount !== null;
      }
    },
    methods: {
      loadCount() {
        if (this.count !== null) {
          this.currentCount = this.count;
        } else if (this.url !== null) {
          if (! this.isInGroup) {
            ProcessMaker.apiClient.get(this.url)
              .then(response => {
                this.setCount(response.data.meta.total);
              })
              .catch(error => {
                this.show();
              });
          }
        }
      },
      setCount(count) {
        this.currentCount = count;
        this.show();
      },
      show() {
        this.loaded = true;
      }
    }
  }
</script>
