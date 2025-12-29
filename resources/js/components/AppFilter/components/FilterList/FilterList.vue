
<script>
import {classNames} from "../../../../utilities/css";
export default {
  props: {
    active: Boolean,
    index: Number,
    tag: [String, Object],
  },
  data() {
    return {
      isActive: false,
    }
  },

  computed: {
    className() {
      return classNames('mb-2 md:mb-0 flex items-center rounded-md pr-2 text-base font-medium gap-2 md:border md:border-surface-subdued md:cursor-pointer border border-interactive bg-white', this.isActive ? 'bg-white' : 'bg-white/50 grayscale-70 text-interactive/40')
    },
    classDoctor() {
      return classNames('relative w-full md:h-44 md:flex md:flex-col md:border md:rounded-t-2xl md:rounded-b-md [&_img]:hidden md:[&_img]:block [&_img]:absolute md:[&_img]:max-w-30 lg:[&_img]:max-w-36 [&_img]:bottom-11 [&_img]:left-1/2 [&_img]:-translate-x-1/2 text-center [&_img]:transition-transform [&_img]:origin-bottom [&_img]:hover:scale-125', this.isActive ? 'md:bg-white md:border-transparent md:[&_img]:scale-125' : 'md:bg-body-gray md:border-white')
    },

    isActive() {
      return this.active;
    },
  },

  methods: {
    toggle(key) {
      this.isActive = !this.isActive
      this.$emit('add-resource', key)
    },
    toggleTag() {
      this.$emit('toggle', this.index);
    },
  },
}
</script>
