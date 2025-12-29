<template>
  <div>
    <div class="flex items-center justify-between">
      <label :for="uniqId" class="block text-base">
        {{ label }}
      </label>
    </div>
    <div v-if="type === 'phone'" class="mt-1 flex">
      <span
        class="rounded-l-md border border-default px-5 inline-flex border-r-0 justify-center items-center text-black text-xl pt-1"
      >
        +7
      </span>
      <input
        :id="uniqId"
        :name="name"
        type="text"
        :placeholder="placeholder"
        :required="required"
        ref="phone"
        inputmode="numeric"
        :value="value"
        @keydown="onPhoneKeyDown"
        @paste="onPhonePaste"
        @input="onPhoneInput"
        @blur="handleInput"
        @change="handleInput"
        class="text-xl text-black block w-full rounded-r-md border border-default px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none"
      />
    </div>
    <div v-else class="mt-1">
      <input
        :id="uniqId"
        :type="type || 'text'"
        :placeholder="placeholder"
        :name="name"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :value="value"
        @input="handleInput"
        required=""
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
import phoneInput from "../../mixins/phoneInput";
import { classNames } from "../../utilities/css";

export default {
  mixins: [phoneInput],
  props: {
    label: String,
    id: String,
    name: String,
    placeholder: String,
    required: Boolean,
    autofocus: Boolean,
    autocomplete: String,
    value: String,
    type: String,
    error: [Boolean, String],
  },

  computed: {
    uniqId() {
      return this.id || this._uid;
    },

    className() {
      return classNames(
        "text-xl text-black block w-full rounded-md border border-default px-5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none",
        this.type === 'date' ? 'pt-2.5 pb-2' : 'py-2.5'
      );
    },
  },

  methods: {
    handleInput(e) {
      this.$emit("input", e.target.value);
    },

    handleChange(e) {
      this.$emit("change", e.target.value);
    },
  },
};
</script>
