<script>
import axios from "axios";
export default {
  props: {
    initialQuery: {
      type: String,
      default: '',
    },
    liveSearchUrl: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      searchQuery: this.initialQuery,
      searchResults: [],
      searchTimeout: null,
      blurTimeout: null,
      requestController: null,
      requestId: 0,
      isFocused: false,
      isLoading: false,
      hasSearched: false,
      searchError: false,
    };
  },
  computed: {
    canSearch() {
      return this.searchQuery.trim().length >= 2;
    },
    showResults() {
      return this.isFocused && this.canSearch && (this.isLoading || this.hasSearched || this.searchError);
    },
  },
  methods: {
    performSearch() {
      const requestId = ++this.requestId;

      clearTimeout(this.searchTimeout);
      this.cancelRequest();
      this.searchResults = [];
      this.hasSearched = false;
      this.searchError = false;

      if (!this.canSearch) {
        this.isLoading = false;
        return;
      }

      this.searchTimeout = setTimeout(() => {
        this.fetchResults(requestId);
      }, 300);
    },
    fetchResults(requestId) {
      this.requestController = new AbortController();
      this.isLoading = true;

      axios.get(this.liveSearchUrl, {
        params: {
          query: this.searchQuery.trim(),
        },
        signal: this.requestController.signal,
      })
        .then(response => {
          if (requestId !== this.requestId) {
            return;
          }

          this.searchResults = response.data;
          this.hasSearched = true;
        })
        .catch(error => {
          if (requestId !== this.requestId || error.code === 'ERR_CANCELED') {
            return;
          }

          this.searchError = true;
        })
        .finally(() => {
          if (requestId === this.requestId) {
            this.isLoading = false;
            this.requestController = null;
          }
        });
    },
    handleFocus() {
      clearTimeout(this.blurTimeout);
      this.isFocused = true;

      if (this.canSearch && !this.isLoading && !this.hasSearched && !this.searchError) {
        this.performSearch();
      }
    },
    handleBlur() {
      this.blurTimeout = setTimeout(() => {
        this.isFocused = false;
      }, 200);
    },
    cancelHideResults() {
      clearTimeout(this.blurTimeout);
    },
    cancelRequest() {
      if (this.requestController) {
        this.requestController.abort();
        this.requestController = null;
      }
    },
    getResultLink(result) {
      return result.handle;
    },
  },
  beforeDestroy() {
    clearTimeout(this.searchTimeout);
    clearTimeout(this.blurTimeout);
    this.cancelRequest();
    this.requestId += 1;
  },
};
</script>
