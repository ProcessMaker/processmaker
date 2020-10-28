<template>
    <div class="multiselect-icons">
      <b-input-group>
        <multiselect
          ref="multiselect"
          v-model="icon"
          track-by="value"
          label="label"
          :show-labels="false"
          :placeholder="placeholder"
          :options="list"
          :multiple="false"
          :searchable="true"
          :internal-search="false"
          :allow-empty="false"
          @search-change="onSearch"
          @open="onOpen"
          @close="onClose"
          >
          <template slot="noResult">
            <div class="multiselect-no-result text-muted my-2 text-center w-100" v-if="allowCustom">
              <strong>{{ $t('No icons found.') }}</strong>
              <br>
              {{ $t('Try a different search or') }}
              <br>
              <a class="text-primary link-upload" @click="triggerUpload">{{ $t('upload a custom icon') }}</a>.
            </div>
            <div class="multiselect-no-result text-muted my-2 text-center w-100" v-else>
              <strong>{{ $t('No icons found.') }}</strong>
              <br>
              {{ $t('Try a different search.') }}
            </div>
          </template>
          <template slot="singleLabel" slot-scope="props">
            <span v-if="props.option">
              <i class="fas fa-fw" :class="'fa-'+props.option.value"></i> {{ props.option.label }}
            </span>
          </template>
          <template slot="placeholder">
            <span v-if="this.file">
              {{ $t('Custom Icon File') }} <span class="text-muted" v-if="this.fileName">({{ this.fileName }})</span>
            </span>
          </template>
          <template slot="option" slot-scope="props">
            <div class="icon-square" @mouseover="onHover(props.option)">
              <i class="fas fa-fw" :class="'fa-'+props.option.value"></i>
            </div>
          </template>
        </multiselect>
        <b-input-group-append class="multiselect-icons-upload" v-if="allowCustom">
          <file-upload-button ref="fileUploadButton" accept="image/png, image/svg+xml, image/gif" v-model="uploadedFile" variant="secondary" v-b-tooltip="{title: $t('Upload Custom Icon')}"><i class="fas fa-fw fa-upload"></i></file-upload-button>
        </b-input-group-append>
      </b-input-group>
    </div>
</template>

<script>
    import Icons from './Icons';
    import FileUploadButton from './FileUploadButton';
    export default {
        components: {
          FileUploadButton,
        },
        props: {
          value: {
            required: false,
          },
          allowCustom: {
            type: Boolean,
            default: true,
          },
          default: {
            type: String,
            default: 'search',
          }
        },
        data() {
          return {
            all: Icons.list(),
            file: null,
            fileName: null,
            icon: null,
            list: {},
            loading: true,
            placeholder: this.$t('Icon'),
            query: '',
            uploadedFile: null,
          };
        },
        computed: {
          isOpen() {
            return this.$refs.multiselect.isOpen;
          },
          iconAndFile() {
            if (this.allowCustom) {
              return {
                icon: this.icon ? this.icon.value : null,
                file: this.file
              };
            } else {
              return this.icon ? this.icon.value : null;
            }
          }
        },
        watch: {
          value() {
            if (this.allowCustom) {
              this.icon = this.find(this.value.icon);
              this.file = this.value.file;
            } else {
              this.icon = this.find(this.value);
            }
          },
          icon(value) {
            if (value) {
              this.file = null;
              this.fileName = null;
              if (this.allowCustom) {
                this.$refs.fileUploadButton.reset();
              }
              this.$emit('input', this.iconAndFile);
            }
          },
          file(value) {
            if (value) {
              this.icon = null;
              this.$refs.multiselect.deactivate();
              this.$emit('input', this.iconAndFile);
            }
          },
          uploadedFile(value) {
            if (value) {
              if (value.size > 2000){
                this.$emit('error', this.$t("The custom icon file is too large. File size must be less than 2KB."));
              } else {
                this.file = value;
                this.fileName = value.name;
                this.uploadedFile = null;
                var reader = new FileReader();
                reader.readAsDataURL(this.file);
                reader.onload = ()=> {
                  this.file = reader.result;
                };                
              }    
            }
          }
        },
        beforeMount() {
          this.list = this.all;
        },
        mounted() {
          if (this.allowCustom) {
            this.icon = this.find(this.value.icon);
            this.file = this.value.file;
          } else {
            if (this.value) {
              this.icon = this.find(this.value);
            } else {
              this.icon = this.find(this.default);
            }
          }
        },
        methods: {
          onSearch(query) {
            if (query.length) {
              this.query = query.toLowerCase();
            } else {
              if (this.isOpen) {
                this.query = '';
              }
            }
            
            if (this.query.length) {
              this.list = this.all.filter(icon => {
                return icon.search.includes(this.query);
              });
            } else {
              this.list = this.all;
            }
          },
          onOpen() {
            this.$refs.multiselect.search = this.query;
          },
          onClose() {
            this.placeholder = this.$t('Icon');
          },
          find(value) {
            return this.all.find(icon => icon.value == value);
          },
          onHover(icon) {
            this.placeholder = icon.label;
          },
          triggerUpload() {
            this.$refs.fileUploadButton.trigger();
          }
        }
    };
</script>

<style lang="scss">
  $iconSize: 29px;
  $multiselect-height: 38px;

  .multiselect-icons {
    .input-group {
      width: 100%;
    }
       
    .multiselect {
      display: inline-block !important;
      position: relative;
      -webkit-box-flex: 1;
          -ms-flex: 1 1 0%;
              flex: 1 1 0%;
      min-width: 0;
      margin-bottom: 0;
    }
    
    .multiselect,
    .multiselect__tags {
      height: $multiselect-height;
      min-height: $multiselect-height;
      max-height: $multiselect-height;
    }
    
    .multiselect__tags {
      overflow: hidden;
    }
    
    .multiselect__content {
      padding: 7px;
    }
    
    .multiselect__element {
      display: inline-block;
    }
    
    .multiselect__element .multiselect__option {
      display: block;
      height: auto;
      margin: 0;
      padding: 0;
      width: auto;
    }

    .icon-square {
      color: #788793;
      font-size: $iconSize;
      padding: $iconSize / 1.5;
      text-align: center;
    }

    .multiselect__option--highlight {
      background: #eee;
    }
        
    .multiselect__option--selected {
      background: #3397E1;
      .icon-square {
        color: white;
      }
    }
    
    .multiselect__input::placeholder {
      color: #788793;
      opacity: .5;
    }
    
    .multiselect__placeholder {
      color: #212529;
      font-size: 16px;
      margin-top: -4px;
      padding: 0 !important;
    }
    
    .multiselect-no-result {
      line-height: 1.5rem;
    }
    
    .link-upload {
      cursor: pointer;
    }
  }
</style>
