<label class="inline-flex py-1 cursor-pointer">
                                <span class="flex flex-auto items-stretch w-6 h-6 mr-2 [&_>_*]:w-full">
                                    <span class="relative">
                                        <input
                                            type="checkbox"
                                            name="{{ $name }}"
                                            {{ $checked == 1 ? 'checked': '' }}
                                            class="peer absolute inset-0 w-px h-px m-0 p-0 overflow-hidden [clip-path:inset(50%)]"
                                            aria-invalid="false" role="checkbox" value="1">
                                        <span
                                            class="block relative w-full h-full border rounded-md peer-checked:border-interactive peer-focus:ring-action-primary peer-focus:ring-2 peer-focus:ring-offset-1 peer-checked::ring-transparent"></span>
                                        <span
                                            class="absolute top-1 left-1 w-4 h-4 peer-checked:bg-interactive origin-center pointer-events-none rounded"></span>
                                    </span>
                                </span>
    <span class="whitespace-nowrap pt-px">{{ $slot }}</span>
</label>
