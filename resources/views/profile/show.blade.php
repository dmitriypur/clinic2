<x-profile-layout>
    <h1 class="font-semibold text-2xl md:text-4xl text-center md:text-left text-heading">Персональные
        данные</h1>
    <div class="grid md:grid-cols-9">
        <div class="col-span-5">
            <form method="POST" action="{{ route('profile.update') }}">
                @method('PUT')
                @csrf
                <div class="mt-6 space-y-3">
                    <x-profile-input
                        id="last_name"
                        name="last_name"
                        type="text"
                        label="Фамилия:"
                        value="{{ old('last_name') ?? $user->last_name }}"
                    >
                        @error('last_name')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>
                    <x-profile-input
                        id="name"
                        name="name"
                        type="text"
                        label="Имя"
                        value="{{ old('name') ?? $user->name }}"
                    >
                        @error('name')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>
                    <x-profile-input
                        id="middle_name"
                        name="middle_name"
                        type="text"
                        label="Отчество:"
                        value="{{ old('middle_name') ?? $user->middle_name }}"
                    >
                        @error('middle_name')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>
                    <x-profile-input
                        id="birthday"
                        name="birthday"
                        type="date"
                        label="Дата рождения:"
                        value="{{ old('birthday') ?? $user->birthday }}"
                    >
                        @error('birthday')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>
                    <x-profile-input id="phone"
                                     name="phone"
                                     type="text"
                                     label="Телефон:"
                                     value="{{ old('phone') ?? $user->phone }}"
                    >
                        @error('phone')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>
                    <x-profile-input
                        id="email"
                        name="email"
                        type="email"
                        label="E-mail:"
                        value="{{ old('email') ?? $user->email }}"
                    >
                        @error('email')
                        <x-slot:error>
                            {{ $message }}
                        </x-slot:error>
                        @enderror
                    </x-profile-input>

                    <div class="h-1 w-full"></div>
                    <x-checkbox
                        name="accept_sms_notifications"
                        checked="{{ old('accept_sms_notifications') ? old('accept_sms_notifications') === true : $user->accept_sms_notifications === true }}"
                    >Получать информационную рассылку по SMS
                    </x-checkbox>

                    <x-checkbox
                        name="accept_sms_promotions"
                        checked="{{ old('accept_sms_promotions') ? old('accept_sms_promotions') === 'true' : $user->accept_sms_promotions === true }}"
                    >Получать рассылку об акциях по SMS
                    </x-checkbox>

                    <div class="h-1 w-full"></div>

                    <x-button-secondary type="submit">Изменить персональные данные</x-button-secondary>
                    @if(!empty($successMessage))
                        <div
                            class="text-action-primary font-medium"> {{ $successMessage }}</div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-profile-layout>
