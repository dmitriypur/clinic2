<template>
  <div>
    <label :for="finalId" class="block text-base">{{ label }}</label>
    <div class="mt-1">
      <input v-if="mask"
             v-mask="mask"
             :id="finalId"
             :name="name"
             type="text"
             :placeholder="placeholder"
             :autocomplete="autocomplete"
             @input="handleChange"
             :value="value"
             :required="requiredIndicator"
             :maxlength="maxLength"
             :class="className"
      />
      <input v-else
             :id="finalId"
             :name="name"
             type="text"
             :placeholder="placeholder"
             :autocomplete="autocomplete"
             @input="handleChange"
             :value="value"
             :required="requiredIndicator"
             :maxlength="maxLength"
             :class="className"
      />
    </div>
    <p
      v-if="error"
      class="mt-3 text-sm leading-6 text-critical"
      v-text="error"
    />
  </div>
</template>
<script>
import {classNames} from "../../../../utilities/css.js";

export default {
  props: {
    id: String,
    label: String,
    name: String,
    value: String,
    autocomplete: String,
    placeholder: String,
    error: [String],
    mask: [String, Array],
    requiredIndicator: Boolean,
    maxLength: Number,
  },

  computed: {
    finalId() {
      return this.id || this._uid
    },

    className() {
      return classNames(
        'text-xl text-black block w-full rounded-md border px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none',
        this.error ? 'border-red-500' : 'border-default'
      )
    }
  },

  methods: {
    handleChange(event) {
      this.$emit('input', event.target.value)
    }
  }
}
</script>
