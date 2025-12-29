export default {
  data() {
    return {
      daClass: '_dynamic_adapt_',
      daType: 'max',
      daItems: [],
      daMedia: []
    };
  },
  mounted() {
    this.initDynamicAdapt();
  },
  beforeDestroy() {
    this.cleanupMedia();
  },
  methods: {
    initDynamicAdapt() {
      const refKeys = Object.keys(this.$refs);
      this.daItems = [];

      refKeys.forEach(key => {
        const el = this.$refs[key];
        if (!el || !el.dataset || !el.dataset.da) return;

        const [selector, bp = '767', place = 'last'] = el.dataset.da.split(',').map(s => s.trim());
        const destination = this.$refs[selector];
        if (!destination) return;

        this.daItems.push({
          el,
          parent: el.parentNode,
          destination,
          breakpoint: bp,
          place,
          index: this.indexInParent(el.parentNode, el)
        });
      });

      this.sortDaItems();

      const breakpoints = [...new Set(this.daItems.map(i => `(${this.daType}-width: ${i.breakpoint}px),${i.breakpoint}`))];

      breakpoints.forEach(str => {
        const [mq, bp] = str.split(',');
        const mm = window.matchMedia(mq);
        const group = this.daItems.filter(i => i.breakpoint === bp);

        const handler = () => this.handleMatch(mm, group);
        mm.addListener(handler);
        handler();

        this.daMedia.push({ mm, handler });
      });
    },

    cleanupMedia() {
      this.daMedia.forEach(({ mm, handler }) => mm.removeListener(handler));
    },

    handleMatch(mm, group) {
      if (mm.matches) {
        group.forEach(i => {
          i.index = this.indexInParent(i.parent, i.el);
          this.moveTo(i.place, i.el, i.destination);
        });
      } else {
        [...group].reverse().forEach(i => {
          if (i.el.classList.contains(this.daClass)) {
            this.moveBack(i.parent, i.el, i.index);
          }
        });
      }
    },

    moveTo(place, el, dest) {
      el.classList.add(this.daClass);
      if (place === 'last' || place >= dest.children.length) {
        dest.appendChild(el);
      } else if (place === 'first') {
        dest.insertBefore(el, dest.firstChild);
      } else {
        dest.insertBefore(el, dest.children[place]);
      }
    },

    moveBack(parent, el, index) {
      el.classList.remove(this.daClass);
      const ref = parent.children[index];
      ref ? parent.insertBefore(el, ref) : parent.appendChild(el);
    },

    indexInParent(parent, el) {
      return Array.prototype.indexOf.call(parent.children, el);
    },

    sortDaItems() {
      const order = this.daType === 'min' ? 1 : -1;
      this.daItems.sort((a, b) => {
        if (a.breakpoint === b.breakpoint) {
          if (a.place === b.place) return 0;
          if (a.place === 'first' || b.place === 'last') return -1 * order;
          if (a.place === 'last' || b.place === 'first') return 1 * order;
          return (a.place - b.place) * order;
        }
        return (a.breakpoint - b.breakpoint) * order;
      });
    }
  }
};
