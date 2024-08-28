<template>
  <div>
      <b-form-group
          :description="$t('Use a transparent PNG at {{size}} pixels for best results.', { size: '292x52'})"
          :label="$t('Custom Login Logo')"
          label-for="custom-login-logo"
          class="mb-3"
          :state="fieldState('fileLogin')"
          :invalid-feedback="errorMessage('fileLogin')"
      >
          <b-form-file
              id="custom-login-logo"
              :placeholder="placeholder(fileLogin, $t('Choose a login logo image'))"
              ref="customFileLogin"
              accept="image/x-png,image/gif,image/jpeg"
              @change.prevent="onFileChangeLogin"
          ></b-form-file>
      </b-form-group>
    
      <b-form-group
          :description="$t('Use a transparent PNG at {{size}} pixels for best results.', { size: '150x40'})"
          :label="$t('Custom Logo')"
          label-for="custom-logo"
          class="mb-3"
          :state="fieldState('fileLogo')"
          :invalid-feedback="errorMessage('fileLogo')"
      >
          <b-form-file
              id="custom-logo"
              :placeholder="placeholder(fileLogo, $t('Choose logo image'))"
              ref="customFileLogo"
              accept="image/x-png,image/gif,image/jpeg"
              @change.prevent="onFileChangeLogo"
          ></b-form-file>
      </b-form-group>
    
      <b-form-group
          :description="$t('Use a transparent PNG at {{size}} pixels for best results.', { size: '40x40'})"
          :label="$t('Custom Icon')"
          label-for="custom-icon"
          class="mb-3"
          :state="fieldState('fileIcon')"
          :invalid-feedback="errorMessage('fileIcon')"
      >
          <b-form-file
              id="custom-icon"
              :placeholder="placeholder(fileIcon, $t('Choose icon image'))"
              ref="customFileIcon"
              accept="image/x-png,image/gif,image/jpeg"
              @change.prevent="onFileChangeIcon"
          ></b-form-file>
      </b-form-group>
      
      <b-form-group
          :description="$t('Use a transparent PNG at {{size}} pixels for best results.', { size: '32x32'})"
          :label="$t('Custom Favicon')"
          label-for="custom-favicon"
          class="mb-3"
          :state="fieldState('fileFavicon')"
          :invalid-feedback="errorMessage('fileFavicon')"
      >
          <b-form-file
              id="custom-favicon"
              :placeholder="placeholder(fileFavicon, $t('Choose icon image'))"
              ref="customFileFavicon"
              accept="image/x-png,image/gif,image/jpeg"
              @change.prevent="onFileChangeFavicon"
          ></b-form-file>
      </b-form-group>
      
      <b-form-group
          :description="$t('Enter the alt text that should accompany the logos and icon.')"
          label="Alternative Text"
          label-for="alt-text"
          class="mb-3"
          :state="fieldState('altText')"
          :invalid-feedback="errorMessage('altText')"
      >
          <b-form-input v-model="altText" id="alt-text"></b-form-input>
      </b-form-group>
      
      <b-form-group
          :description="$t('Click on the color value to select custom colors.')"
          :label="$t('Custom Colors')"
          label-for="color-list"
          class="mb-3"
          :state="fieldState('variables')"
          :invalid-feedback="errorMessage('variables')"
      >
          <ul class="list-group w-100" id="color-list">
              <li class="list-group-item" v-for="item in customColors">
                  <color-picker :color="item.value" :title="item.title" v-model="item.value"></color-picker>
              </li>
          </ul>                        
      </b-form-group>
    
      <b-form-group
          :description="$t('Select which font to use throughout the system.')"
          :label="$t('Custom Font')"
          label-for="custom-font"
          class="mb-3"
          :state="fieldState('sansSerifFont')"
          :invalid-feedback="errorMessage('sansSerifFont')"
      >
          <label for="custom-font" class="d-none">{{$t('Select which font to use throughout the system.')}}</label>
          <multiselect id="custom-font"
                       v-model="selectedSansSerifFont"
                       :placeholder="$t('Type to search')"
                       :options="fontsDefault"
                       :multiple="false"
                       :show-labels="false"
                       :searchable="true"
                       track-by="id"
                       label="title"
          >
              <template slot="noResult">
                  {{ $t('No elements found. Consider changing the search query.') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="singleLabel" slot-scope="props">
                  <span :style="font(props.option.id)">{{ props.option.title }}</span>
              </template>
              <template slot="option" slot-scope="props">
                  <span :style="font(props.option.id)">{{ props.option.title }}</span>
              </template>
          </multiselect>
      </b-form-group>
    
      <b-form-group
          :description="$t('Enter footer HTML to display on the login page.')"
          :label="$t('Login Page Footer')"
          label-for="login-footer"
          class="mb-3"
          :state="fieldState('loginFooter')"
          :invalid-feedback="errorMessage('loginFooter')"
      >
          <editor id="login-footer" v-model="loginFooter" :init="editorSettings"></editor>
      </b-form-group>
      
      <br>
      <div class="d-flex group-button">
          <b-button variant="outline-danger" :disabled="isLoading" @click="onReset">
              <i class="fas fa-undo"></i> {{ $t('Reset') }}
          </b-button>
          
          <b-button variant="outline-secondary" class="ml-auto" @click="onClose">
              {{ $t('Cancel') }}
          </b-button>
          
          <b-button variant="secondary" class="ml-3" :disabled="isLoading" @click="onSubmit">
              {{ $t('Save') }}
          </b-button>
      </div>
      <b-modal id="modalLoading"
             ref="modalLoading"
             v-bind:hide-header="true"
             v-bind:hide-footer="true"
             v-bind:no-close-on-backdrop="true"
             v-bind:no-close-on-esc="true"
             v-bind:hide-header-close="true">
        <div class="container text-center">
            <div class="icon-container m-4">
                <svg class="lds-gear" width="100%" height="50%" xmlns="http://www.w3.org/2000/svg"
                     xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100"
                     preserveAspectRatio="xMidYMid">
                    <g transform="translate(50 50)">
                        <g transform="rotate(248.825)">
                            <animateTransform attributeName="transform" type="rotate" values="0;360"
                                              keyTimes="0;1" dur="4.7s"
                                              repeatCount="indefinite">
                            </animateTransform>
                            <path d="M37.43995192304605 -6.5 L47.43995192304605 -6.5 L47.43995192304605 6.5 L37.43995192304605 6.5 A38 38 0 0 1 35.67394948182593 13.090810836924174 L35.67394948182593 13.090810836924174 L44.33420351967032 18.090810836924174 L37.83420351967032 29.34914108612188 L29.17394948182593 24.34914108612188 A38 38 0 0 1 24.34914108612188 29.17394948182593 L24.34914108612188 29.17394948182593 L29.34914108612188 37.83420351967032 L18.090810836924184 44.33420351967032 L13.090810836924183 35.67394948182593 A38 38 0 0 1 6.5 37.43995192304605 L6.5 37.43995192304605 L6.500000000000001 47.43995192304605 L-6.499999999999995 47.43995192304606 L-6.499999999999996 37.43995192304606 A38 38 0 0 1 -13.09081083692417 35.67394948182593 L-13.09081083692417 35.67394948182593 L-18.09081083692417 44.33420351967032 L-29.34914108612187 37.834203519670325 L-24.349141086121872 29.173949481825936 A38 38 0 0 1 -29.17394948182592 24.34914108612189 L-29.17394948182592 24.34914108612189 L-37.83420351967031 29.349141086121893 L-44.33420351967031 18.0908108369242 L-35.67394948182592 13.090810836924193 A38 38 0 0 1 -37.43995192304605 6.5000000000000036 L-37.43995192304605 6.5000000000000036 L-47.43995192304605 6.500000000000004 L-47.43995192304606 -6.499999999999993 L-37.43995192304606 -6.499999999999994 A38 38 0 0 1 -35.67394948182593 -13.090810836924167 L-35.67394948182593 -13.090810836924167 L-44.33420351967032 -18.090810836924163 L-37.834203519670325 -29.34914108612187 L-29.173949481825936 -24.34914108612187 A38 38 0 0 1 -24.349141086121893 -29.17394948182592 L-24.349141086121893 -29.17394948182592 L-29.349141086121897 -37.834203519670304 L-18.0908108369242 -44.334203519670304 L-13.090810836924195 -35.67394948182592 A38 38 0 0 1 -6.500000000000005 -37.43995192304605 L-6.500000000000005 -37.43995192304605 L-6.500000000000007 -47.43995192304605 L6.49999999999999 -47.43995192304606 L6.499999999999992 -37.43995192304606 A38 38 0 0 1 13.090810836924149 -35.67394948182594 L13.090810836924149 -35.67394948182594 L18.090810836924142 -44.33420351967033 L29.349141086121847 -37.83420351967034 L24.349141086121854 -29.17394948182595 A38 38 0 0 1 29.17394948182592 -24.349141086121893 L29.17394948182592 -24.349141086121893 L37.834203519670304 -29.349141086121897 L44.334203519670304 -18.0908108369242 L35.67394948182592 -13.090810836924197 A38 38 0 0 1 37.43995192304605 -6.500000000000007 M0 -20A20 20 0 1 0 0 20 A20 20 0 1 0 0 -20"></path>
                        </g>
                    </g>
                </svg>
            </div>
            <h3 class="display-6">{{ $t('Regenerating CSS Files') }}</h3>
            <p class="lead">{{ $t('Please wait while the files are generated. The screen will be updated when finished.') }}</p>
        </div>
    </b-modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isLoading: false,
      altText: '',
      loginFooter: '',
      editorSettings: {
        content_css: '/css/app.css',
        content_style: "body {padding: 10px}",
        menubar: false,
        plugins: [ 'link', 'lists', 'code' ],
        toolbar: 'code | undo redo | link | styleselect fontsizeselect | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
        skin: false,
        relative_urls: false,
        remove_script_host: false,
      },
      config: config,
      key: 'css-override',
      fileLogin: {
        file: null,
        selectedFile: null,
      },
      fileLogo: {
        file: null,
        selectedFile: null,
      },
      fileIcon: {
        file: null,
        selectedFile: null,
      },
      fileFavicon: {
        file: null,
        selectedFile: null,
      },
      colors: null,
      selectedSansSerifFont: {
        'id': "'Open Sans'",
        'title': 'Default Font'
      },
      colorDefault: [
        {
          id: '$primary',
          value: '#2773F3',
          title: this.$t('Primary')
        },
        {
          id: '$secondary',
          value: '#728092',
          title: this.$t('Secondary')
        },
        {
          id: '$success',
          value: '#0CA442',
          title: this.$t('Success')
        },
        {
          id: '$info',
          value: '#104A75',
          title: this.$t('Info')
        },
        {
          id: '$warning',
          value: '#EC8E00',
          title: this.$t('Warning')
        },
        {
          id: '$danger',
          value: '#EC5962',
          title: this.$t('Danger')
        },
        {
          id: '$dark',
          value: '#20242A',
          title: this.$t('Dark')
        },
        {
          id: '$light',
          value: '#FFFFFF',
          title: this.$t('Light')
        },
      ],
      fontsDefault: [
        {
          'id': "'Open Sans'",
          'title': 'Default Font'
        },
        {
          "id": "Menlo, Monaco, Consolas, 'Courier New', monospace",
          "title": "Mono Type"
        },
        {
          "id": "Arial",
          "title": "Arial"
        },
        {
          "id": "'Arial Black'",
          "title": "Arial Black"
        },
        {
          "id": "Bookman",
          "title": "Bookman"
        },
        {
          "id": "'Comic Sans MS'",
          "title": "Comic Sans MS"
        },
        {
          "id": "'Courier New'",
          "title": "Courier New"
        },
        {
          "id": "Garamond",
          "title": "Garamond"
        },
        {
          "id": "Georgia",
          "title": "Georgia"
        },
        {
          "id": "Helvetica",
          "title": "Helvetica"
        },
        {
          "id": "Impact",
          "title": "Impact"
        },
        {
          "id": "'Times New Roman'",
          "title": "Times New Roman"
        },
        {
          "id": "Verdana",
          "title": "Verdana"
        },
        {
          "id": "Palatino",
          "title": "Palatino"
        },
        {
          "id": "'Trebuchet MS'",
          "title": "Trebuchet MS"
        },
      ],
      errors: {
        'login': null,
        'logo': null,
        'icon': null,
        'favicon': null,
        'colors': null,
      }
    }
  },
  watch: {
    config: {
      immediate: true,
      handler() {
        if (!this.config || !this.config.config) {
          return;
        }
        if (this.config.config.login != "null") {
          this.fileLogin.selectedFile = this.config.config.login;
        }
        if (this.config.config.logo != "null") {
          this.fileLogo.selectedFile = this.config.config.logo;
        }
        if (this.config.config.icon != "null") {
          this.fileIcon.selectedFile = this.config.config.icon;
        }
        if (this.config.config.favicon != "null") {
          this.fileFavicon.selectedFile = this.config.config.favicon;
        }
        if (this.config.config.sansSerifFont != "null") {
          this.selectedSansSerifFont = JSON.parse(this.config.config.sansSerifFont);
        }
      }
    },
  },
  computed: {
    customColors() {
      let data = this.colorDefault;
      if (this.config && this.config.config.variables) {
        data = JSON.parse(this.config.config.variables);
      }
      return data;
    }
  },
  mounted() {
    let userID = document.head.querySelector('meta[name="user-id"]');
    window.Echo.private(
      `ProcessMaker.Models.User.${userID.content}`
    ).notification(response => {
      if (response.type == "ProcessMaker\\Notifications\\SassCompiledNotification") {
        ProcessMaker.alert(this.$t('The styles were recompiled.'), 'success');
        this.onClose();
      }
    });

    this.loginFooter = _.get(loginFooterSetting, 'config.html', '');
    this.altText = altTextSetting;
  },
  methods: {
    placeholder(object, string) {
      let filename = _.get(object, 'selectedFile');
      
      if (filename) {
        return filename;
      } else {
        return string;
      }
    },
    fieldState(field) {
      if (_.get(this.errors, field)) {
        return false;
      } else {
        return true;
      }
    },
    errorMessage(field) {
      const errors = _.get(this.errors, field);
      if (errors) {
        return errors.join(' ');
      } else {
        return '';
      }
    },
    resetErrors() {
      this.errors = Object.assign({}, {
        login: null,
        logo: null,
        icon: null,
        colors: null
      });
    },
    onClose() {
      window.location.href = '/admin/customize-ui';
    },
    onSubmit() {
      this.resetErrors();

      let formData = new FormData();
      formData.append('key', this.key);
      formData.append('fileLoginName', this.fileLogin.selectedFile);
      formData.append('fileLogoName', this.fileLogo.selectedFile);
      formData.append('fileIconName', this.fileIcon.selectedFile);
      formData.append('fileFaviconName', this.fileFavicon.selectedFile);
      formData.append('fileLogin', this.fileLogin.file);
      formData.append('fileLogo', this.fileLogo.file);
      formData.append('fileIcon', this.fileIcon.file);
      formData.append('fileFavicon', this.fileFavicon.file);
      formData.append('variables', JSON.stringify(this.customColors));
      formData.append('sansSerifFont', JSON.stringify(this.selectedSansSerifFont));
      formData.append('loginFooter', this.loginFooter);
      formData.append('altText', this.altText);

      this.onCreate(formData);
    },
    onReset() {
      let that = this;
      ProcessMaker.confirmModal(
        this.$t('Caution!'),
        "<b>" + this.$t('Are you sure you want to reset the UI styles?') + "</b>",
        "",
        () => {
          let formData = new FormData();
          formData.append('key', this.key);
          formData.append('reset', 'true');
          formData.append('fileLogoName', '');
          formData.append('fileIconName', '');
          formData.append('fileFaviconName', '');
          formData.append('fileLogo', '');
          formData.append('fileIcon', '');
          formData.append('fileFavicon', '');
          formData.append('variables', JSON.stringify(this.colorDefault));
          formData.append('sansSerifFont', JSON.stringify({id:"'Open Sans'", value:'Open Sans'}));
          formData.append('loginFooter', '');
          formData.append('altText', 'ProcessMaker');

          this.onCreate(formData);
        }
      );
    },
    onCreate(data) {
      this.isLoading = true;
      ProcessMaker.apiClient.post('customize-ui', data)
        .then(response => {
          this.$refs.modalLoading.show();
        })
        .catch(error => {
          ProcessMaker.alert(
            _.get(error, 'response.data.message', this.$t('The given data was invalid.')),
            'danger'
          );
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onUpdate(data) {
      ProcessMaker.apiClient.put('customize-ui', data)
        .then(response => {
          this.$refs.modalLoading.show();
        })
        .catch(error => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    font(value) {
      return 'font-family:' + value;
    },
    browseLogin() {
      this.$refs.customFileLogin.click();
    },
    onFileChangeLogin(e) {
      let files = e.target.files || e.dataTransfer.files;

      if (!files.length) {
        return;
      }

      this.fileLogin.selectedFile = files[0].name;
      this.fileLogin.file = files[0];
    },
    browseLogo() {
      this.$refs.customFileLogo.click();
    },
    onFileChangeLogo(e) {
      let files = e.target.files || e.dataTransfer.files;

      if (!files.length) {
        return;
      }

      this.fileLogo.selectedFile = files[0].name;
      this.fileLogo.file = files[0];
    },
    browseIcon() {
      this.$refs.customFileIcon.click();
    },
    onFileChangeIcon(e) {
      let files = e.target.files || e.dataTransfer.files;

      if (!files.length) {
        return;
      }

      this.fileIcon.selectedFile = files[0].name;
      this.fileIcon.file = files[0];
    },
    browseFavicon() {
      this.$refs.customFileFavicon.click();
    },
    onFileChangeFavicon(e) {
      let files = e.target.files || e.dataTransfer.files;
    
      if (!files.length) {
        return;
      }
    
      this.fileFavicon.selectedFile = files[0].name;
      this.fileFavicon.file = files[0];
    },
  }
};
</script>

<style>
.group-button {
  padding-bottom: 50px;
}
</style>
