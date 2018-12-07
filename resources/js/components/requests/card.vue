<template>
    <span>
        <div v-for="event in process.events" class="processes">
            <div @click="newRequestLink(process, event)" class="process-card">
                <div class="inner">
                    <div>
                        <span class="name" v-html="transformedName"></span>
                        <span v-if="process.events.length > 1">: {{event.name}}</span>
                        <i v-show="spin===process.id + '.' + event.id" class="fa fa-spinner fa-spin fa-fw"></i>
                    </div>
                    <div ref="description" class="description" v-html="truncatedDescription"></div>
                </div>
            </div>
        </div>
    </span>
</template>

<script>
export default {
  props: ["name", "description", "filter", "id", "process"],
  data() {
      return {
          spin: 0
      }
  },
  methods: {
    newRequestLink(process, event) {
      //Start a process
      this.spin = process.id + '.' + event.id;
      let startEventId = event.id;
      window.ProcessMaker.apiClient.post('/process_events/'+this.process.id + '?event=' + startEventId)
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
        return '<span class="filtered">' + match + "</span>";
      });
    },
    truncatedDescription() {
      if (!this.process.description) {
          return '<span class="filtered"></span>';
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
        return '<span class="filtered">' + match + "</span>";
      });
    }
  }
};
</script>

<style lang="scss" scoped>
  .process-card /deep/ .filtered {
    color: #3397e1;
  }

.process-card {
  cursor: pointer;
  width: 354px;
  height: 91px;
  border-radius: 2px;
  background-color: #f7f9fa;
  border: solid 1px #eeeeee;
  margin-right: 16px;
  margin-bottom: 16px;
  border-left: 2px solid #00bf9c;

  .inner {
    padding: 14px 23px;
    height: 91px;

    .name {
      font-size: 14px;
      font-weight: bold;
      font-style: normal;
      font-stretch: normal;
      line-height: normal;
      letter-spacing: normal;
      color: #313131;
      overflow: hidden;
      white-space: nowrap;
      -ms-text-overflow: ellipsis;
      text-overflow: ellipsis;
      width: 100%;
    }

    .description {
      margin-top: 9px;
      font-size: 12px;
      height: 32px;
      font-weight: normal;
      font-style: normal;
      font-stretch: normal;
      line-height: normal;
      letter-spacing: normal;
      color: #788793;
      overflow: hidden;
    }
  }

  &:hover {
    .name {
      color: #00bf9c; 
      & /deep/ .filtered {
        color: #00bf9c; 
      }
    }

 }
}
</style>


