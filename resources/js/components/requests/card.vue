<template>
    <div>
        <b-card class="w-50 mb-3" bg-variant="light" v-for="event in process.startEvents" :key="event.id">
            <b-card-title>
              <span v-html="transformedName" class="font-weight-bold"></span>
              <span v-if="process.startEvents.length > 1">: {{event.name}}</span>
            </b-card-title>
            <b-card-text class="text-muted" v-html="truncatedDescription"></b-card-text>
            <a href="#" @click="newRequestLink(process, event)" class="stretched-link hidden">{{ name }}</a>
        </b-card>
    </div>
</template>

<script>
import { TooltipPlugin } from 'bootstrap-vue/es/components'
Vue.use(TooltipPlugin)
  export default {
    props: ["name", "description", "filter", "id", "process"],
    data() {
      return {
        disabled: false,
        spin: 0,
        showtip: true,
      }
    },
    methods: {
      newRequestLink(process, event) {
        if (this.disabled) {
          return
        }
        this.disabled = true;
        //Start a process
        this.spin = process.id + '.' + event.id;
        let startEventId = event.id;
        window.ProcessMaker.apiClient.post('/process_events/' + this.process.id + '?event=' + startEventId)
          .then((response) => {
            this.spin = 0;
            var instance = response.data;
            window.location = '/requests';
          })
      }
    },
    computed: {
      transformedName() {
        return this.process.name.replace(new RegExp(this.filter, "gi"), match => {
          return '<span class="text-primary">' + match + "</span>";
        });
      },
      truncatedDescription() {
        if (!this.process.description) {
          return '<span class="text-primary"></span>';
        }
        let result = "";
        let container = this.$refs.description;
        let wordArray = this.process.description.split(" ");
        // Number of maximum characters we want for our description
        let maxLength = 100;
        let word = null;
        while ((word = wordArray.shift())) {
          if (result.length + word.length + 1 <= maxLength) {
            result = result + " " + word;
            continue;
          } else {
            break;
          }
        }
        return result.replace(new RegExp(this.filter, "gi"), match => {
          return '<span class="text-primary">' + match + "</span>";
        });
      }
    }
  };
</script>

<style lang="scss" scoped>
    .card {
      cursor: hand;
    }

    .card-title {
      font-size: 1em;
    }

    .card-text {
      font-size: 0.75em;
    }

    // .process-card {
    //     cursor: pointer;
    //     width: 354px;
    //     height: 91px;
    //     border-radius: 2px;
    //     background-color: #f7f9fa;
    //     border: solid 1px #eeeeee;
    //     margin-right: 16px;
    //     margin-bottom: 16px;
    //     border-left: 2px solid #00bf9c;

    //     .inner {
    //         padding: 14px 23px;
    //         height: 91px;

    //         .name {
    //             font-size: 14px;
    //             font-weight: bold;
    //             font-style: normal;
    //             font-stretch: normal;
    //             line-height: normal;
    //             letter-spacing: normal;
    //             color: #313131;
    //             overflow: hidden;
    //             white-space: nowrap;
    //             -ms-text-overflow: ellipsis;
    //             text-overflow: ellipsis;
    //             width: 100%;
    //         }

    //         .description {
    //             margin-top: 9px;
    //             font-size: 12px;
    //             height: 32px;
    //             font-weight: normal;
    //             font-style: normal;
    //             font-stretch: normal;
    //             line-height: normal;
    //             letter-spacing: normal;
    //             color: #788793;
    //             overflow: hidden;
    //         }

    //         .warn {
    //             font-style: italic
    //         }
    //     }

    //     &:hover {
    //         .name {
    //             color: #00bf9c;

    //             & /deep/ .filtered {
    //                 color: #00bf9c;
    //             }
    //         }

    //     }
    // }
</style>


