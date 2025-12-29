<div>
    <div class="group border-b border-black focus-within:border-action-primary relative">
        <input type="{{ $type }}"
               name="{{ $name }}"
               id="{{ $id }}"
               value="{{ $value }}"
               class="peer block w-full border-0 border-b border-transparent p-0 pb-1 pt-6 focus:ring-0 focus:border-action-primary outline-none text-lg/none font-medium"
               placeholder=" "/>
        <label for="{{ $id }}"
               class="absolute text-subdued duration-300 transform -translate-y-4 top-4 z-10 origin-[0] left-0 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4 cursor-text">{{ $label }}</label>
    </div>
    @isset($error)
        <p class="mt-2 text-sm text-critical">{{ $error }}</p>
    @endisset
</div>
