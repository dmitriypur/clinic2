<template>
  <div class="accessibility-toggle">
    <!-- Кнопка переключения -->
    <span
      @click="toggleAccessibility"
      :class="[
        'inline-flex w-9 h-9 text-icon-interactive cursor-pointer',
      ]"
      :title="isAccessibilityMode ? 'Выключить версию для слабовидящих' : 'Включить версию для слабовидящих'"
    >
      <svg viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" class="stroke-[0.3] stroke-current fill-current">
        <path d="M24.9375 19C24.9375 20.5747 24.3119 22.0849 23.1984 23.1984C22.0849 24.3119 20.5747 24.9375 19 24.9375C17.4253 24.9375 15.9151 24.3119 14.8016 23.1984C13.6881 22.0849 13.0625 20.5747 13.0625 19C13.0625 17.4253 13.6881 15.9151 14.8016 14.8016C15.9151 13.6881 17.4253 13.0625 19 13.0625C20.5747 13.0625 22.0849 13.6881 23.1984 14.8016C24.3119 15.9151 24.9375 17.4253 24.9375 19Z" fill="#1F3462"/>
        <path d="M0 19C0 19 7.125 5.9375 19 5.9375C30.875 5.9375 38 19 38 19C38 19 30.875 32.0625 19 32.0625C7.125 32.0625 0 19 0 19ZM19 27.3125C21.2046 27.3125 23.3189 26.4367 24.8778 24.8778C26.4367 23.3189 27.3125 21.2046 27.3125 19C27.3125 16.7954 26.4367 14.6811 24.8778 13.1222C23.3189 11.5633 21.2046 10.6875 19 10.6875C16.7954 10.6875 14.6811 11.5633 13.1222 13.1222C11.5633 14.6811 10.6875 16.7954 10.6875 19C10.6875 21.2046 11.5633 23.3189 13.1222 24.8778C14.6811 26.4367 16.7954 27.3125 19 27.3125Z" fill="#1F3462"/>
    </svg>
      <!-- <span class="hidden lg:inline">
        {{ isAccessibilityMode ? 'Обычная версия' : 'Версия для слабовидящих' }}
      </span> -->
    </span>

    <!-- Панель настроек для слабовидящих -->
    <div
      v-if="isAccessibilityMode"
      class="accessibility-panel fixed top-20 right-4 lg:top-32 lg:right-8 mt-16 bg-white border-2 border-interactive rounded-lg shadow-xl z-40 min-w-[280px] transition-all duration-300 z-50"
      :class="{ 'collapsed': isPanelCollapsed }"
    >
      <!-- Заголовок с кнопкой сворачивания -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200">
        <h3 class="text-lg font-bold text-interactive">Настройки отображения</h3>
        <button
          @click="togglePanel"
          class="text-interactive hover:text-interactive-hovered transition-colors p-1"
          :title="isPanelCollapsed ? 'Развернуть панель' : 'Свернуть панель'"
        >
          <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': isPanelCollapsed }" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
          </svg>
        </button>
      </div>

      <!-- Содержимое панели -->
      <div v-show="!isPanelCollapsed" class="p-4">

      <!-- Размер шрифта -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-interactive mb-2">Размер шрифта:</label>
        <div class="flex gap-2">
          <button
            v-for="size in fontSizes"
            :key="size.value"
            @click="setFontSize(size.value)"
            :class="[
              'px-3 py-1 rounded border text-sm font-medium transition-colors',
              currentFontSize === size.value
                ? 'bg-interactive text-white border-interactive'
                : 'bg-white text-interactive border-interactive hover:bg-interactive hover:text-white'
            ]"
          >
            {{ size.label }}
          </button>
        </div>
      </div>

      <!-- Цветовая схема -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-interactive mb-2">Цветовая схема:</label>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="theme in colorThemes"
            :key="theme.value"
            @click="setColorTheme(theme.value)"
            :class="[
              'px-3 py-2 rounded border text-sm font-medium transition-colors',
              currentColorTheme === theme.value
                ? 'bg-interactive text-white border-interactive'
                : 'bg-white text-interactive border-interactive hover:bg-interactive hover:text-white'
            ]"
          >
            {{ theme.label }}
          </button>
        </div>
      </div>

      <!-- Дополнительные настройки -->
      <div class="space-y-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="settings.removeImages"
            @change="updateSettings"
            class="w-4 h-4 text-interactive border-2 border-interactive rounded focus:ring-interactive"
          >
          <span class="text-sm text-interactive">Скрыть изображения</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="settings.increaseLineHeight"
            @change="updateSettings"
            class="w-4 h-4 text-interactive border-2 border-interactive rounded focus:ring-interactive"
          >
          <span class="text-sm text-interactive">Увеличить межстрочный интервал</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="settings.removeAnimations"
            @change="updateSettings"
            class="w-4 h-4 text-interactive border-2 border-interactive rounded focus:ring-interactive"
          >
          <span class="text-sm text-interactive">Отключить анимации</span>
        </label>
      </div>

      <!-- Кнопка сброса -->
      <button
        @click="resetSettings"
        class="w-full mt-4 px-3 py-2 bg-gray-200 text-interactive rounded border border-interactive hover:bg-gray-300 transition-colors text-sm font-medium"
      >
        Сбросить настройки
      </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AccessibilityToggle',

  data() {
    return {
      isAccessibilityMode: false,
      isPanelCollapsed: false,
      currentFontSize: 'normal',
      currentColorTheme: 'normal',
      settings: {
        removeImages: false,
        increaseLineHeight: false,
        removeAnimations: false
      },
      fontSizes: [
        { label: 'Обычный', value: 'normal' },
        { label: 'Крупный', value: 'large' },
        { label: 'Очень крупный', value: 'extra-large' }
      ],
      colorThemes: [
        { label: 'Обычная', value: 'normal' },
        { label: 'Темная', value: 'dark' },
        { label: 'Контрастная', value: 'high-contrast' },
        { label: 'Инвертированная', value: 'inverted' }
      ]
    }
  },

  mounted() {
    this.loadSettings();
    this.applySettings();
  },

  methods: {
    toggleAccessibility() {
      this.isAccessibilityMode = !this.isAccessibilityMode;
      this.saveSettings();
      this.applySettings();
    },

    togglePanel() {
      this.isPanelCollapsed = !this.isPanelCollapsed;
      this.saveSettings();
    },

    setFontSize(size) {
      this.currentFontSize = size;
      this.saveSettings();
      this.applySettings();
    },

    setColorTheme(theme) {
      this.currentColorTheme = theme;
      this.saveSettings();
      this.applySettings();
    },

    updateSettings() {
      this.saveSettings();
      this.applySettings();
    },

    resetSettings() {
      this.currentFontSize = 'normal';
      this.currentColorTheme = 'normal';
      this.settings = {
        removeImages: false,
        increaseLineHeight: false,
        removeAnimations: false
      };
      this.saveSettings();
      this.applySettings();
    },

    loadSettings() {
      const saved = localStorage.getItem('accessibility-settings');
      if (saved) {
        const data = JSON.parse(saved);
        this.isAccessibilityMode = data.isAccessibilityMode || false;
        this.isPanelCollapsed = data.isPanelCollapsed || false;
        this.currentFontSize = data.currentFontSize || 'normal';
        this.currentColorTheme = data.currentColorTheme || 'normal';
        this.settings = { ...this.settings, ...data.settings };
      }
    },

    saveSettings() {
      const data = {
        isAccessibilityMode: this.isAccessibilityMode,
        isPanelCollapsed: this.isPanelCollapsed,
        currentFontSize: this.currentFontSize,
        currentColorTheme: this.currentColorTheme,
        settings: this.settings
      };
      localStorage.setItem('accessibility-settings', JSON.stringify(data));
    },

    applySettings() {
      const body = document.body;
      const html = document.documentElement;

      // Удаляем все предыдущие классы доступности
      body.classList.remove(
        'accessibility-mode',
        'font-size-normal', 'font-size-large', 'font-size-extra-large',
        'theme-normal', 'theme-dark', 'theme-high-contrast', 'theme-inverted',
        'remove-images', 'increase-line-height', 'remove-animations'
      );
      body.removeAttribute('data-accessibility');

      if (this.isAccessibilityMode) {
        body.classList.add('accessibility-mode');
        body.classList.add(`font-size-${this.currentFontSize}`);
        body.classList.add(`theme-${this.currentColorTheme}`);

        body.setAttribute('data-accessibility', true);

        if (this.settings.removeImages) body.classList.add('remove-images');
        if (this.settings.increaseLineHeight) body.classList.add('increase-line-height');
        if (this.settings.removeAnimations) body.classList.add('remove-animations');
      }
    }
  }
}
</script>

<style scoped>
.accessibility-btn {
  transition: all 0.3s ease;
}

.accessibility-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(31, 52, 98, 0.3);
}

.accessibility-active {
  background: linear-gradient(135deg, #1F3462 0%, #40598E 100%) !important;
}

.accessibility-panel {
  animation: slideIn 0.3s ease-out;
}

.accessibility-panel.collapsed {
  height: auto;
  min-height: auto;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
