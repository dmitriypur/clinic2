const FRAME_GENDERS = ["boy", "girl"];

export function toggleFrameCatalogFilter(values, value) {
  return values.includes(value)
    ? values.filter((item) => item !== value)
    : [...values, value];
}

export function normalizeGenderFilters(genders) {
  const normalized = genders.filter(
    (gender, index) => FRAME_GENDERS.includes(gender) && genders.indexOf(gender) === index
  );

  return normalized.length === FRAME_GENDERS.length ? [] : normalized;
}

export function frameCatalogPageSize(isTabletOrDesktop) {
  return isTabletOrDesktop ? 6 : 4;
}

export function isLatestFrameCatalogRequest(requestId, latestRequestId) {
  return requestId === latestRequestId;
}

export function buildFrameCatalogQuery({ ages, genders, offset, limit }) {
  const params = new URLSearchParams();

  ages.forEach((age) => params.append("ages[]", String(age)));
  normalizeGenderFilters(genders).forEach((gender) => {
    params.append("genders[]", gender);
  });
  params.set("offset", String(offset));
  params.set("limit", String(limit));

  return params.toString();
}

export default {
  props: {
    endpoint: {
      type: String,
      required: true,
    },
    initialTotal: {
      type: Number,
      required: true,
    },
    initialCount: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      activeAges: [],
      activeGenders: [],
      total: this.initialTotal,
      loadedCount: this.initialCount,
      isTabletOrDesktop: false,
      isLoading: false,
      errorMessage: "",
      latestRequestId: 0,
      mediaQuery: null,
    };
  },

  computed: {
    batchSize() {
      return frameCatalogPageSize(this.isTabletOrDesktop);
    },

    hasMoreFrames() {
      return this.loadedCount < this.total;
    },

    nextBatchCount() {
      return Math.min(Math.max(this.total - this.loadedCount, 0), this.batchSize);
    },
  },

  mounted() {
    this.mediaQuery = window.matchMedia("(min-width: 768px)");
    this.isTabletOrDesktop = this.mediaQuery.matches;
    this.normalizeInitialCards();
    this.addBreakpointListener();
  },

  beforeDestroy() {
    this.removeBreakpointListener();
  },

  methods: {
    toggleAge(age) {
      this.activeAges = toggleFrameCatalogFilter(this.activeAges, age);
      this.loadFrames(false);
    },

    toggleGender(gender) {
      this.activeGenders = toggleFrameCatalogFilter(this.activeGenders, gender);
      this.loadFrames(false);
    },

    resetFilters() {
      this.activeAges = [];
      this.activeGenders = [];
      this.loadFrames(false);
    },

    showMore() {
      if (!this.isLoading && this.hasMoreFrames) {
        this.loadFrames(true);
      }
    },

    async loadFrames(append) {
      const requestId = ++this.latestRequestId;
      const offset = append ? this.loadedCount : 0;
      const query = buildFrameCatalogQuery({
        ages: this.activeAges,
        genders: this.activeGenders,
        offset,
        limit: this.batchSize,
      });

      this.isLoading = true;
      this.errorMessage = "";

      try {
        const response = await fetch(`${this.endpoint}?${query}`, {
          headers: { Accept: "application/json" },
        });

        if (!response.ok) {
          throw new Error(`Frame catalog request failed with ${response.status}`);
        }

        const result = await response.json();

        if (!isLatestFrameCatalogRequest(requestId, this.latestRequestId)) {
          return;
        }

        if (append) {
          this.$refs.items.insertAdjacentHTML("beforeend", result.html);
        } else {
          this.$refs.items.innerHTML = result.html;
        }

        this.total = Number(result.total) || 0;
        this.loadedCount = Number(result.nextOffset) || 0;
        this.$refs.items.dataset.frameCatalogReady = "";
      } catch (error) {
        if (isLatestFrameCatalogRequest(requestId, this.latestRequestId)) {
          this.errorMessage = "Не удалось загрузить оправы. Попробуйте ещё раз.";
        }
      } finally {
        if (isLatestFrameCatalogRequest(requestId, this.latestRequestId)) {
          this.isLoading = false;
        }
      }
    },

    normalizeInitialCards() {
      const cards = Array.from(this.$refs.items.children);
      const visibleCount = Math.min(cards.length, this.batchSize);

      cards.slice(visibleCount).forEach((card) => card.remove());
      this.loadedCount = visibleCount;
      this.$refs.items.dataset.frameCatalogReady = "";
    },

    handleBreakpointChange(event) {
      this.isTabletOrDesktop = event.matches;
      this.loadFrames(false);
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
