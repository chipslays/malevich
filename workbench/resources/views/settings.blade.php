@extends('examples::layouts.workspace')
@section('title', 'Настройки')

@section('content')
    <div class="mb-8">
        <x-heading as="h1" size="xl">Настройки пространства</x-heading>
        <x-text color="neutral" class="mt-2">Профиль, команда и личные предпочтения.</x-text>
    </div>

    <form method="post" action="{{ route('examples.settings.save') }}">
        @csrf
        @if ($errors->any())<x-text color="danger" class="mb-5">Проверьте отмеченные поля.</x-text>@endif
        <x-tabs value="general" class="gap-6">
            <x-tabs.list aria-label="Разделы настроек">
                <x-tabs.tab value="general">Основное</x-tabs.tab>
                <x-tabs.tab value="notifications">Уведомления</x-tabs.tab>
                <x-tabs.tab value="appearance">Внешний вид</x-tabs.tab>
            </x-tabs.list>

            <x-tabs.panel value="general">
                <x-panel padding="none">
                    <section id="profile" class="grid grid-cols-[180px_minmax(0,1fr)] gap-10 px-7 py-7">
                        <div><x-heading size="sm">Профиль</x-heading><x-text color="neutral" class="mt-2">Информация, которую видит ваша команда.</x-text></div>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=128&h=128&q=85" :name="$settings['name']" size="xl" radius="full" loading="eager" />
                                <div><x-text weight="medium">{{ $settings['name'] }}</x-text><x-text color="neutral">Владелец пространства</x-text></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <x-input name="name" label="Имя и фамилия" :value="old('name', $settings['name'])" autocomplete="name" required />
                                <x-input name="email" type="email" label="Электронная почта" :value="old('email', $settings['email'])" autocomplete="email" required />
                            </div>
                            <x-field label="Видимость профиля">
                                <div class="flex gap-6 pt-1"><x-radio name="visibility" value="team" :checked="old('visibility', $settings['visibility']) === 'team'" label="Для команды" /><x-radio name="visibility" value="private" :checked="old('visibility', $settings['visibility']) === 'private'" label="Только для меня" /></div>
                            </x-field>
                        </div>
                    </section>
                    <section id="workspace" class="grid grid-cols-[180px_minmax(0,1fr)] gap-10 border-t border-line px-7 py-7">
                        <div><x-heading size="sm">Пространство</x-heading><x-text color="neutral" class="mt-2">Общие параметры и комфортная загрузка.</x-text></div>
                        <div class="space-y-6">
                            <x-input name="workspace" label="Название" :value="old('workspace', $settings['workspace'])" required />
                            <div class="grid grid-cols-2 gap-4">
                                <x-select name="timezone" label="Часовой пояс" :value="old('timezone', $settings['timezone'])" :options="['Europe/Moscow' => 'Москва · UTC+3', 'Europe/Astrakhan' => 'Астрахань · UTC+4', 'Asia/Tbilisi' => 'Тбилиси · UTC+4', 'Europe/Berlin' => 'Берлин']" />
                                <x-select name="language" label="Язык уведомлений" :value="old('language', $settings['language'])" :options="['ru' => 'Русский', 'en' => 'English']" />
                            </div>
                            <x-slider name="capacity" :min="25" :max="100" :step="5" :value="old('capacity', $settings['capacity'])" label="Комфортная загрузка" show-value suffix="%" />
                        </div>
                    </section>
                </x-panel>
            </x-tabs.panel>

            <x-tabs.panel value="notifications">
                <x-panel padding="none">
                    @foreach ([['email_updates', 'Обновления проектов', 'Изменения статусов и завершённые этапы.'], ['mentions', 'Упоминания и комментарии', 'Когда коллегам нужна ваша обратная связь.'], ['weekly_digest', 'Итоги недели', 'Короткое письмо о том, как продвигается команда.']] as [$name, $label, $description])
                        <div class="border-line px-7 py-6 not-first:border-t"><input type="hidden" name="{{ $name }}" value="0"><x-switch :name="$name" value="1" :checked="(bool) old($name, $settings[$name])" :label="$label" :description="$description" class="w-full flex-row-reverse justify-between" /></div>
                    @endforeach
                </x-panel>
            </x-tabs.panel>

            <x-tabs.panel value="appearance">
                <x-panel padding="none">
                    <section class="flex items-center justify-between gap-8 px-7 py-7">
                        <div><x-heading size="sm">Цветовая тема</x-heading><x-text color="neutral" class="mt-1">Сохраняется в браузере и применяется сразу.</x-text></div>
                        <x-button.group aria-label="Цветовая тема">
                            <x-button type="button" color="neutral" variant="outline" data-theme-choice="light" aria-pressed="false" class="aria-pressed:bg-contrast aria-pressed:text-on-contrast"><x-slot:leading><x-icon name="lucide-sun" class="size-4 shrink-0" /></x-slot:leading>Светлая</x-button>
                            <x-button type="button" color="neutral" variant="outline" data-theme-choice="dark" aria-pressed="false" class="aria-pressed:bg-contrast aria-pressed:text-on-contrast"><x-slot:leading><x-icon name="lucide-moon" class="size-4 shrink-0" /></x-slot:leading>Тёмная</x-button>
                            <x-button type="button" color="neutral" variant="outline" data-theme-choice="system" aria-pressed="false" class="aria-pressed:bg-contrast aria-pressed:text-on-contrast" aria-label="Системная тема"><x-icon name="lucide-monitor" class="size-4 shrink-0" /></x-button>
                        </x-button.group>
                    </section>
                    <section class="grid grid-cols-2 gap-8 border-t border-line px-7 py-7">
                        <div><input type="hidden" name="compact" value="0"><x-checkbox name="compact" value="1" :checked="(bool) old('compact', $settings['compact'])" label="Компактная таблица" description="Меньше отступов в списке проектов." /></div>
                        <div><input type="hidden" name="tips" value="0"><x-checkbox name="tips" value="1" :checked="(bool) old('tips', $settings['tips'])" label="Полезные подсказки" description="Дополнительные пояснения в интерфейсе." /></div>
                    </section>
                </x-panel>
            </x-tabs.panel>
        </x-tabs>
        <div class="mt-6 flex items-center justify-end gap-3"><x-button type="reset" color="neutral" variant="outline">Сбросить</x-button><x-button type="submit" color="contrast">Сохранить изменения</x-button></div>
    </form>
@endsection
