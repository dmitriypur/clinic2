export default {
  data() {
    return {
      activeAge: null,
      activeGender: null,
      allItems: [],
      filteredItems: [],
      visibleCount: 0,
      isDesktop: false,
      isLoading: false,
      mediaQuery: null,
    };
  },

  computed: {
    hasResults() {
      return this.filteredItems.length > 0;
    },

    hasMoreFrames() {
      return this.visibleCount < this.filteredItems.length;
    },

    batchSize() {
      return this.isDesktop ? 6 : 4;
    },

    initialVisibleCount() {
      return this.isDesktop ? 6 : 4;
    },

    nextBatchCount() {
      return Math.min(this.filteredItems.length - this.visibleCount, this.batchSize);
    },
  },

  mounted() {
    const deferredTemplate = document.createElement("template");
    const deferredMarkup = this.$refs.deferredItems
      ? this.$refs.deferredItems.value.trim()
      : "";

    deferredTemplate.innerHTML = deferredMarkup;
    this.allItems = [
      ...Array.from(this.$refs.items.children),
      ...Array.from(deferredTemplate.content.children),
    ];

    if (this.$refs.deferredItems) {
      this.$refs.deferredItems.value = "";
    }

    this.mediaQuery = window.matchMedia("(min-width: 768px)");
    this.isDesktop = this.mediaQuery.matches;
    this.addBreakpointListener();
    this.applyFilters();
  },

  beforeDestroy() {
    this.removeBreakpointListener();
  },

  methods: {
    toggleAge(age) {
      this.activeAge = this.activeAge === age ? null : age;
      this.applyFilters();
    },

    toggleGender(gender) {
      this.activeGender = this.activeGender === gender ? null : gender;
      this.applyFilters();
    },

    resetFilters() {
      this.activeAge = null;
      this.activeGender = null;
      this.applyFilters();
    },

    applyFilters() {
      this.filteredItems = this.allItems.filter((item) => {
        const matchesAge = !this.activeAge || item.dataset.frameCatalogAge === this.activeAge;
        const matchesGender = !this.activeGender
          || item.dataset.frameCatalogGender === this.activeGender
          || item.dataset.frameCatalogGender === "unisex";

        return matchesAge && matchesGender;
      });
      this.visibleCount = Math.min(this.initialVisibleCount, this.filteredItems.length);
      this.renderVisibleItems();
    },

    showMore() {
      if (this.isLoading || !this.hasMoreFrames) {
        return;
      }

      this.isLoading = true;
      this.visibleCount = Math.min(
        this.visibleCount + this.batchSize,
        this.filteredItems.length
      );
      this.renderVisibleItems();
      this.isLoading = false;
    },

    renderVisibleItems() {
      const fragment = document.createDocumentFragment();

      this.filteredItems.slice(0, this.visibleCount).forEach((item) => {
        fragment.appendChild(item);
      });

      this.$refs.items.replaceChildren(fragment);
    },

    handleBreakpointChange(event) {
      this.isDesktop = event.matches;
      this.applyFilters();
    },

    addBreakpointListener() {
      if (this.mediaQuery.addEventListener) {
        this.mediaQuery.addEventListener("change", this.handleBreakpointChange);
        return;
      }

      this.mediaQuery.addListener(this.handleBreakpointChange);
    },

    removeBreakpointListener() {
      if (!this.mediaQuery) {
        return;
      }

      if (this.mediaQuery.removeEventListener) {
        this.mediaQuery.removeEventListener("change", this.handleBreakpointChange);
        return;
      }

      this.mediaQuery.removeListener(this.handleBreakpointChange);
    },
  },
};
