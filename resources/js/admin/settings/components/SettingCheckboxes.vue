<template>
  <div class="setting-text">
    <div v-if="input === null || !input.length" class="font-italic text-black-50">
      Empty
    </div>
    <div v-else>
      {{ trimmed(text) }}
    </div>
    <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden" @show="onShowModal">
      <template v-slot:modal-header>
        <div>
          <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
          <h5 class="mb-0" v-else>{{ setting.key }}</h5>
          <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
        </div>
        <button type="button" :aria-label="$t('Close')" class="close" @click="onCancel">×</button>
      </template>
      <div v-if="error" class="alert alert-danger">
        {{ $t('Unable to load options.') }}
      </div>
      <div v-else-if="loaded">
        <!-- Search Input - Only show when pagination is enabled -->
        <div v-if="isPaginated" class="mb-3 search-container">
          <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input
              id="search-input"
              v-model="searchQuery"
              type="text"
              class="search-input"
              :placeholder="$t('Search here')"
              @keyup.enter="onSearchSubmit"
            />
            <i class="fas fa-times clear-icon" @click="clearSearch"></i>
          </div>
        </div>
        
        <!-- No results message -->
        <div v-if="isPaginated && searchQuery && options.length === 0 && loaded" class="text-center py-3">
          <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
          <small class="text-muted">{{ $t('No Data Available') }}</small>
        </div>
        
        <b-form-group
          v-else
          :invalid-feedback="$t(message)"
          :state="invalid ? false : null"
        >
          <b-form-checkbox-group
            v-model="transformed"
            :options="options"
            :switches="switches"
            stacked
            @change="onChanged"
          />
        </b-form-group>
        
        <!-- Pagination Controls -->
        <div v-if="isPaginated && totalPages > 1" class="d-flex justify-content-between align-items-center mt-3">
          <div class="pagination-info">
            <small class="text-muted">
              {{ $t('Display') }} {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalItems) }} 
              {{ $t('of') }} {{ totalItems }} {{ $t('items') }}
            </small>
          </div>
          <div class="pagination-controls">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary me-2" 
              @click="onPageChange(currentPage - 1)"
              :disabled="currentPage <= 1"
            >
              <i class="fas fa-chevron-left"></i> {{ $t('Previous') }}
            </button>
            <span class="mx-2">
              {{ $t('Page') }} {{ currentPage }} {{ $t('of') }} {{ totalPages }}
            </span>
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary ms-2" 
              @click="onPageChange(currentPage + 1)"
              :disabled="currentPage >= totalPages"
            >
              {{ $t('Next') }} <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
      <div v-else>
        <i class="fas fa-cog fa-spin text-secondary"></i> {{ $t('Loading...') }}
      </div>
      <div slot="modal-footer" class="w-100 m-0 d-flex">
        <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
            {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-secondary ml-3" @click="onSave" :disabled="invalid || ! changed">
            {{ $t('Save')}}
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  name: 'SettingCheckboxes',
  mixins: [settingMixin],
  props: ['value', 'setting'],
  data() {
    return {
      message: '',
      invalid: false,
      error: false,
      input: null,
      loaded: false,
      options: [],
      selected: null,
      showModal: false,
      transformed: [],
      // Pagination data
      currentPage: 1,
      perPage: 10,
      totalPages: 0,
      totalItems: 0,
      isPaginated: false,
      // Search data
      searchQuery: '',
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
    display() {
      const options = this.ui('options');
      const keys = Object.keys(options);
      if (keys.includes(this.input)) {
        return options[this.input];
      } else {
        return this.input;
      }
    },
    text() {
      if (this.input && this.input.length) {
        return this.input.join(', ');
      } else {
        return '';
      }
    },
    switches() {
      if (this.ui('switches')) {
        return true;
      } else {
        return false;
      }
    },
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    }
  },
  methods: {
    onChanged() {
      this.invalid = false;
      this.message = '';
    },
    onCancel() {
      this.showModal = false;
    },
    onEdit() {
      this.showModal = true;
    },
    onModalHidden() {
      this.transformed = this.copy(this.input);
      this.error = false;
      if (this.ui('dynamic')) {
        this.loaded = false;
        this.options = [];
        // Reset pagination state
        this.currentPage = 1;
        this.totalPages = 0;
        this.totalItems = 0;
        this.isPaginated = false;
        // Reset search state
        this.searchQuery = '';
      }
      this.invalid = false;
      this.message = '';
    },
    onShowModal() {
      if (! this.loaded && this.ui('dynamic')) {
        this.loadOptions();
      }
    },
    loadOptions(page = 1) {
      let settings = this.ui('dynamic');
      let url = settings.url;
      
      // Check if pagination is enabled
      this.isPaginated = this.ui('pagination') || false;
      
      if (this.isPaginated) {
        // Add pagination and search parameters to URL
        // Expected API parameters:
        // - page: Current page number (starts from 1)
        // - per_page: Number of items per page (default: 10)
        // - search: Search query string (optional)
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', this.perPage);
        
        // Add search parameter if there's a search query
        if (this.searchQuery && this.searchQuery.trim()) {
          params.append('search', this.searchQuery.trim());
        }
        
        // Add existing query parameters if any
        if (url.includes('?')) {
          url += '&' + params.toString();
        } else {
          url += '?' + params.toString();
        }
      }
      
      ProcessMaker.apiClient.get(url).then(response => {
        let data = _.get(response, settings.response);
        if (data) {
          if (this.isPaginated) {
            // Handle paginated response
            this.options = data.data || data;
            this.totalItems = data.total;
            this.totalPages = Math.ceil(data.total / this.perPage);
            this.currentPage = page;
          } else {
            // Handle non-paginated response
            this.options = data.data || data;
            this.totalItems = data.length;
            this.totalPages = 1;
            this.currentPage = 1;
          }
          this.loaded = true;
        }
      }).catch(error => {
        this.error = true;
        console.error('Error loading options:', error);
      });
    },
    onPageChange(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.loaded = false;
        this.loadOptions(page);
      }
    },
    onSearchSubmit() {
      // Execute search when Enter is pressed
      this.performSearch();
    },
    performSearch() {
      // Reset to first page when searching
      this.currentPage = 1;
      this.loaded = false;
      this.loadOptions(1);
    },
    clearSearch() {
      this.searchQuery = '';
      this.performSearch();
    },
    async onSave() {
      const testSettingEndpoint = this.ui('testSettingEndpoint');
      const enabled = this.copy(this.transformed);
      if (testSettingEndpoint) {
        try {
          await ProcessMaker.apiClient.post(testSettingEndpoint, { enabled });
        } catch (error) {
          this.invalid = true;
          this.message = error.response.data.message || error.message;
          return;
        }
      }
      this.input = enabled;
      this.showModal = false;
      this.emitSaved(this.input);
    },
  },
  mounted() {
    if (this.value === null) {
      this.input = [];
    } else {
      this.input = this.value;
    }
    if (! this.ui('dynamic')) {
      this.options = this.ui('options');
      this.loaded = true;
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

  .pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .pagination-info {
    font-size: 0.875rem;
  }

  .pagination-controls .btn {
    min-width: 80px;
  }

  .pagination-controls span {
    font-size: 0.875rem;
    color: $secondary;
    white-space: nowrap;
  }

  // Search input styles - matching the image design
  .search-container {
    .search-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      padding: 0 12px;
      height: 40px;
      transition: border-color 0.2s ease;
      
      &:focus-within {
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
      }
      
      .search-icon {
        color: #6c757d;
        font-size: 14px;
        margin-right: 8px;
        flex-shrink: 0;
      }
      
      .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        color: #333;
        padding: 0;
        
        &::placeholder {
          color: #999;
        }
      }
      
      .clear-icon {
        color: #6c757d;
        font-size: 14px;
        cursor: pointer;
        margin-left: 8px;
        flex-shrink: 0;
        transition: color 0.2s ease;
        
        &:hover {
          color: #333;
        }
      }
    }
  }
</style>
