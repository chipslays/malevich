@extends('examples::layouts.workspace')
@section('title', 'Проекты')

@section('content')
    @php
        $teamNames = \Workbench\App\Http\Controllers\ExamplesController::TEAM;
        $teamNames['АМ'] = session('examples.settings.name', $teamNames['АМ']);
        $statusLabels = \Workbench\App\Http\Controllers\ExamplesController::STATUSES;
        $statusColors = \Workbench\App\Http\Controllers\ExamplesController::STATUS_COLORS;
    @endphp
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><x-heading as="h1" size="xl">Проекты</x-heading><p class="mt-2 text-sm text-ink-muted">Статусы, сроки и участники проектов в одном списке.</p></div>
        <div class="flex gap-2">
            <x-button :href="route('examples.projects.export')" color="neutral" variant="outline"><x-slot:leading><x-icon name="lucide-download" class="size-4 shrink-0" /></x-slot:leading>Экспорт</x-button>
            <x-button data-dialog-open="new-project"><x-slot:leading><x-icon name="lucide-plus" class="size-4 shrink-0" /></x-slot:leading>Новый проект</x-button>
        </div>
    </div>

    <form method="get" action="{{ route('examples.projects') }}" class="mb-5 flex flex-wrap items-center gap-3">
        <x-input name="q" :value="$query" placeholder="Найти проект или клиента" aria-label="Найти проект" class="w-72">
            <x-slot:leading><x-icon name="lucide-search" class="size-4 shrink-0" /></x-slot:leading>
        </x-input>
        <x-select name="status" :value="$status" placeholder="Все статусы" :options="$statusLabels" aria-label="Статус проекта" class="w-40" />
        <x-select name="sort" :value="$sort" :options="['recent' => 'Сначала новые', 'name' => 'По названию', 'progress' => 'По готовности']" aria-label="Порядок проектов" class="w-40" />
        <x-button type="submit" color="contrast" variant="soft">Применить</x-button>
        @if ($query !== '' || $status !== '' || $sort !== 'recent')<x-button :href="route('examples.projects')" color="neutral" variant="ghost">Сбросить</x-button>@endif
    </form>

    <x-panel padding="none">
        <form method="post" action="{{ route('examples.projects.archive') }}">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div class="flex items-center gap-2"><h2 class="text-sm font-semibold">Все проекты</h2><x-badge color="neutral" size="sm">{{ count($projects) }}</x-badge></div>
                <div class="flex items-center gap-3"><span data-selection-count class="text-sm text-ink-muted">Выберите проекты для действия</span><x-button type="submit" color="neutral" variant="outline" size="sm" disabled data-bulk-action>В архив</x-button></div>
            </div>
            @error('projects')<p class="px-5 pt-3 text-sm text-danger-ink">{{ $message }}</p>@enderror
            @if (count($projects))
                <div class="app-scroll overflow-x-auto">
                    <table class="w-full min-w-[760px] text-start">
                        <thead><tr class="border-b border-line bg-surface/50 text-xs text-ink-muted"><th class="w-12 px-5 py-3 font-normal"><x-checkbox data-check-all aria-label="Выбрать все проекты" size="sm" /></th><th class="py-3 pe-4 text-start font-normal">Название проекта</th><th class="py-3 pe-4 text-start font-normal">Статус</th><th class="w-36 py-3 pe-5 text-start font-normal">Готовность</th><th class="py-3 pe-4 text-start font-normal">Команда</th><th class="py-3 pe-5 text-end font-normal">Срок</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($projects as $project)
                                <tr class="transition-colors hover:bg-surface/60">
                                    <td class="px-5 py-5"><x-checkbox name="projects[]" :value="$project['id']" :aria-label="'Выбрать '.$project['name']" size="sm" /></td>
                                    <td class="py-5 pe-4"><button type="button" data-dialog-open="project-{{ $project['id'] }}" class="group flex items-center gap-3 text-start"><x-avatar :name="$project['client']" :initials="$project['initials']" radius="lg" variant="outline" /><span><span class="block text-sm font-semibold group-hover:text-brand-ink">{{ $project['name'] }}</span><span class="mt-1 block text-xs text-ink-muted">{{ $project['category'] }}</span></span></button></td>
                                    <td class="py-5 pe-4"><x-badge :color="$statusColors[$project['status']]" size="sm" dot>{{ $statusLabels[$project['status']] }}</x-badge></td>
                                    <td class="py-5 pe-5"><div class="flex items-center gap-2"><x-progress :value="$project['progress']" :color="$project['status'] === 'done' ? 'success' : 'contrast'" size="sm" /><span class="text-xs tabular-nums text-ink-muted">{{ $project['progress'] }}%</span></div></td>
                                    <td class="py-5 pe-4"><div class="flex -space-x-1.5">@foreach ($project['team'] as $member)<x-avatar :name="$teamNames[$member]" size="sm" class="ring-2 ring-surface-raised" />@endforeach</div></td>
                                    <td class="py-5 pe-5 text-end text-xs whitespace-nowrap text-ink-muted">{{ $project['due'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t border-line px-5 py-4 text-xs text-ink-muted"><span>Найдено проектов: {{ count($projects) }}</span><span>Atelier Studio</span></div>
            @else
                <div class="flex flex-col items-center px-5 py-16 text-center"><span class="mb-4 flex size-12 items-center justify-center rounded-2xl bg-surface"><x-icon name="lucide-folder-search" class="size-6 shrink-0 text-ink-muted" /></span><h3 class="text-sm font-semibold">Ничего не нашлось</h3><p class="mt-2 max-w-xs text-sm leading-5 text-ink-muted">Попробуйте другое название или сбросьте фильтры, чтобы увидеть все проекты.</p><x-button :href="route('examples.projects')" color="contrast" variant="soft" class="mt-5">Сбросить фильтры</x-button></div>
            @endif
        </form>
    </x-panel>
@endsection

@section('dialogs')
    <x-dialog id="new-project" title="Начнём с новой идеи" description="Добавьте основную информацию. Всё остальное можно уточнить в процессе." :data-open-on-load="$errors->hasAny(['project_name', 'client', 'category', 'budget'])">
        <form id="new-project-form" method="post" action="{{ route('examples.projects.store') }}" class="space-y-5">
            @csrf
            <x-input name="project_name" label="Название проекта" placeholder="Например, новый сайт студии" :value="old('project_name')" required autofocus />
            <x-input name="client" label="Клиент" placeholder="Компания или имя" :value="old('client')" required />
            <x-select name="category" label="Направление" :value="old('category', 'Продуктовый дизайн')" :options="array_combine(['Продуктовый дизайн', 'Айдентика', 'Интернет-магазин', 'Мобильное приложение', 'Дизайн-система'], ['Продуктовый дизайн', 'Айдентика', 'Интернет-магазин', 'Мобильное приложение', 'Дизайн-система'])" required />
            <x-input name="budget" type="number" min="0" step="1000" label="Бюджет" :value="old('budget', 100000)" required><x-slot:trailing>₽</x-slot:trailing></x-input>
        </form>
        <x-slot:footer><x-button type="submit" form="new-project-form"><x-slot:leading><x-icon name="lucide-plus" class="size-4 shrink-0" /></x-slot:leading>Создать проект</x-button></x-slot:footer>
    </x-dialog>
    @foreach ($projects as $project)
        <x-dialog :id="'project-'.$project['id']" :title="$project['name']" :description="$project['client'].' · '.$project['category']">
            <div class="space-y-6">
                <div class="flex items-center justify-between"><x-badge :color="$statusColors[$project['status']]" dot>{{ $statusLabels[$project['status']] }}</x-badge><span class="text-sm text-ink-muted">{{ $project['due'] }}</span></div>
                <x-progress :value="$project['progress']" label="Готовность проекта" show-value />
                <x-divider />
                <div class="grid grid-cols-2 gap-5"><div><p class="text-sm text-ink-muted">Бюджет</p><p class="mt-2 text-lg font-semibold tabular-nums">{{ number_format($project['budget'], 0, ',', ' ') }} ₽</p></div><div><p class="text-sm text-ink-muted">Команда</p><div class="mt-2 flex gap-1.5">@foreach ($project['team'] as $member)<x-avatar :name="$teamNames[$member]" size="sm" />@endforeach</div></div></div>
            </div>
        </x-dialog>
    @endforeach
@endsection
