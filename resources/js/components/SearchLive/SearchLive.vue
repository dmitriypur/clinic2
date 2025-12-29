<script>
import axios from "axios";
export default {
  data() {
    return {
      searchQuery: '',
      searchResults: [],
      showResults: false,
      searchTimeout: null
    };
  },
  methods: {
    performSearch() {
      // Задержка перед отправкой запроса (debounce)
      clearTimeout(this.searchTimeout);

      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        return;
      }

      this.searchTimeout = setTimeout(() => {
        axios.get('/live-search', {
          params: {
            query: this.searchQuery
          }
        })
          .then(response => {
            this.searchResults = response.data;
            this.showResults = true;
          })
          .catch(error => {
            console.error('Search error:', error);
          });
      }, 300);
    },
    hideResults() {
      // Небольшая задержка перед скрытием, чтобы можно было кликнуть по результату
      setTimeout(() => {
        this.showResults = false;
      }, 200);
    },
    submitSearch() {
      if (this.searchQuery.trim()) {
        window.location.href = `/search?q=${encodeURIComponent(this.searchQuery)}`;
      }
    },
    getResultLink(result) {
      // Верните ссылку на страницу результата
      return `${result.handle}`;
    },
    handleResultClick(result) {
      this.searchQuery = result.title;
      this.showResults = false;
      window.location.href = this.getResultLink(result);
    }
  }
};
</script>
