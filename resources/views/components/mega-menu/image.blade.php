<div v-show="previewImage && !isMobile" class="accessibility:hidden px-1 pb-2 pt-12 bg-interactive rounded-2xl dropdown-in">
    <div class="w-full h-full max-h-[300px] bg-cover bg-no-repeat bg-center transition-all duration-300 rounded-3xl"
         :style="{ backgroundImage: 'url(/storage/' + previewImage + ')' }"></div>
</div>
