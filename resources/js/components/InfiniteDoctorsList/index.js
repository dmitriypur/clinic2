import { eventBus } from "../../eventBus";

export default {
  props: {
    initialNextPageUrl: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      nextPageUrl: this.initialNextPageUrl,
      isLoading: false,
      loadError: false,
      observer: null,
      supportsIntersectionObserver:
        typeof window !== "undefined" && "IntersectionObserver" in window,
    };
  },

  computed: {
    hasMorePages() {
      return Boolean(this.nextPageUrl);
    },
  },

  mounted() {
    if (!this.supportsIntersectionObserver || !this.hasMorePages) {
      return;
    }

    this.observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;

        if (entry?.isIntersecting) {
          this.loadNextPage();
        }
      },
      {
        rootMargin: "400px 0px",
      }
    );

    this.observer.observe(this.$refs.sentinel);
  },

  beforeDestroy() {
    this.disconnectObserver();
  },

  methods: {
    disconnectObserver() {
      if (this.observer) {
        this.observer.disconnect();
        this.observer = null;
      }
    },

    async loadNextPage() {
      if (this.isLoading || !this.hasMorePages) {
        return;
      }

      this.isLoading = true;
      this.loadError = false;

      try {
        const response = await fetch(this.nextPageUrl, {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error(`Failed with status ${response.status}`);
        }

        const html = await response.text();
        const parsedDocument = new DOMParser().parseFromString(html, "text/html");
        const nextItemsRoot = parsedDocument.querySelector("[data-doctors-items]");
        const nextPaginationRoot = parsedDocument.querySelector("[data-doctors-pagination]");

        if (!nextItemsRoot) {
          throw new Error("Doctors items container not found");
        }

        this.appendItems(nextItemsRoot);
        this.syncPagination(nextPaginationRoot);
        this.nextPageUrl = this.resolveNextPageUrl(nextPaginationRoot);

        if (!this.hasMorePages) {
          this.disconnectObserver();
        }
      } catch (error) {
        this.loadError = true;
      } finally {
        this.isLoading = false;
      }
    },

    appendItems(nextItemsRoot) {
      const fragment = document.createDocumentFragment();

      Array.from(nextItemsRoot.children).forEach((child) => {
        fragment.appendChild(document.importNode(child, true));
      });

      this.$refs.items.appendChild(fragment);
    },

    syncPagination(nextPaginationRoot) {
      if (!this.$refs.pagination) {
        return;
      }

      this.$refs.pagination.innerHTML = nextPaginationRoot
        ? nextPaginationRoot.innerHTML
        : "";
    },

    resolveNextPageUrl(nextPaginationRoot) {
      if (!nextPaginationRoot) {
        return null;
      }

      const nextLink = nextPaginationRoot.querySelector('a[rel="next"]');

      return nextLink ? nextLink.href : null;
    },

    handleContainerClick(event) {
      const videoTrigger = event.target.closest("[data-doctor-video-url]");

      if (videoTrigger) {
        const videoUrl = videoTrigger.dataset.doctorVideoUrl;

        if (videoUrl) {
          event.preventDefault();
          eventBus.$emit("showVideoModal", videoUrl);
        }

        return;
      }

      const callbackTrigger = event.target.closest("[data-doctor-callback-target]");

      if (!callbackTrigger) {
        return;
      }

      event.preventDefault();
      const bookingStartMode = String(
        callbackTrigger.dataset.bookingStartMode || ""
      )
        .trim()
        .toLowerCase();
      const options = bookingStartMode
        ? { bookingStartMode }
        : null;

      eventBus.$emit(
        "showCallbackModal",
        null,
        callbackTrigger.dataset.doctorCallbackTarget || null,
        options
      );
    },
  },
};
