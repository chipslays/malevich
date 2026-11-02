@extends('examples::layouts.workspace')
@section('title', 'Обзор')

@section('content')
    @php
        $teamNames = \Workbench\App\Http\Controllers\ExamplesController::TEAM;
        $teamNames['АМ'] = session('examples.settings.name', $teamNames['АМ']);
        $statusLabels = \Workbench\App\Http\Controllers\ExamplesController::STATUSES;
        $statusColors = \Workbench\App\Http\Controllers\ExamplesController::STATUS_COLORS;
    @endphp
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-sm text-ink-muted">Понедельник, 2 ноября</p>
            <x-heading as="h1" size="xl">Обзор пространства</x-heading>
            <p class="mt-2 text-sm text-ink-muted">Проекты, задачи и загрузка команды.</p>
        </div>
        <x-button :href="route('examples.projects')" color="contrast">
            Все проекты <x-slot:trailing><x-icon name="lucide-arrow-up-right" class="size-4 shrink-0" /></x-slot:trailing>
        </x-button>
    </div>

    <div class="grid grid-cols-4 gap-5">
        @foreach ([['Все проекты', count($projects), 'lucide-folders', 'В одном пространстве'], ['В работе', $activeCount, 'lucide-layers', 'Движемся к результату'], ['На проверке', $reviewCount, 'lucide-circle-check', 'Ждут обратной связи'], ['Команда', '4', 'lucide-users', 'Сильнее вместе']] as [$label, $value, $icon, $caption])
            <x-panel padding="md">
                <div class="flex items-center justify-between gap-3 text-sm text-ink-muted"><span>{{ $label }}</span><x-icon :name="$icon" class="size-4 shrink-0" /></div>
                <p class="mt-4 text-3xl font-semibold tracking-tight tabular-nums">{{ $value }}</p>
                <p class="mt-2 text-xs text-ink-muted">{{ $caption }}</p>
            </x-panel>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)]">
        <div class="space-y-6">
            <x-panel title="Проекты в фокусе" description="Ближайшие этапы и движение к цели" padding="none">
                <x-slot:action><x-badge color="neutral">{{ $activeCount }} в работе</x-badge></x-slot:action>
                <div class="divide-y divide-line">
                    @foreach (array_slice(array_values(array_filter($projects, fn ($p) => $p['status'] !== 'archived')), 0, 4) as $project)
                        <a href="{{ route('examples.projects', ['q' => $project['client']]) }}" class="group flex items-center gap-4 px-5 py-4 transition-colors hover:bg-surface">
                            <x-avatar :name="$project['client']" :initials="$project['initials']" radius="lg" variant="outline" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2"><p class="truncate text-sm font-medium">{{ $project['name'] }}</p><x-badge :color="$statusColors[$project['status']]" size="sm" dot>{{ $statusLabels[$project['status']] }}</x-badge></div>
                                <div class="mt-2.5 flex items-center gap-3"><x-progress :value="$project['progress']" :color="$project['status'] === 'done' ? 'success' : 'brand'" size="sm" class="flex-1" /><span class="text-xs tabular-nums text-ink-muted">{{ $project['progress'] }}%</span></div>
                            </div>
                            <x-icon name="lucide-chevron-right" class="size-4 shrink-0 text-ink-muted/50 group-hover:text-ink" />
                        </a>
                    @endforeach
                </div>
                <x-slot:footer><a href="{{ route('examples.projects') }}" class="inline-flex items-center gap-1.5 pt-4 font-medium text-ink">Ко всем проектам <x-icon name="lucide-arrow-right" class="size-3.5 shrink-0" /></a></x-slot:footer>
            </x-panel>

            <x-panel title="Ритм команды" description="Время, посвящённое проектам за неделю">
                <x-slot:action><x-badge color="neutral" variant="outline">Эта неделя</x-badge></x-slot:action>
                <div class="flex items-baseline gap-2"><span class="text-3xl font-semibold tracking-tight tabular-nums">32,5</span><span class="text-sm text-ink-muted">часа продуктивной работы</span></div>
                <div class="mt-6 grid h-36 grid-cols-7 items-end gap-5" role="img" aria-label="Активность по дням: понедельник 4 часа, вторник 6, среда 5, четверг 7, пятница 6, суббота 3, воскресенье 1,5">
                    @foreach ([['Пн', 57], ['Вт', 86], ['Ср', 71], ['Чт', 100], ['Пт', 86], ['Сб', 43], ['Вс', 21]] as [$day, $height])
                        <div class="flex h-full flex-col items-center justify-end gap-2.5"><div @class(['w-full max-w-12 rounded-t-md', 'bg-brand' => $day === 'Чт', 'bg-brand/20' => $day !== 'Чт']) style="height: {{ $height }}%"></div><span class="text-xs text-ink-muted">{{ $day }}</span></div>
                    @endforeach
                </div>
            </x-panel>
        </div>

        <div class="space-y-6">
            <x-panel title="На сегодня" description="Ближайшие задачи команды">
                <div class="space-y-5">
                    @foreach ($tasks as $task)
                        <form method="post" action="{{ route('examples.tasks.update', $task['id']) }}" data-auto-submit>
                            @csrf
                            <x-checkbox name="completed" value="1" :checked="$completedTasks[$task['id']] ?? false" :label="$task['label']" :description="$task['project'].' · '.$task['time']" :label:class="($completedTasks[$task['id']] ?? false) ? 'line-through text-ink-muted' : ''" />
                            <noscript><x-button type="submit" size="xs" color="neutral">Обновить</x-button></noscript>
                        </form>
                    @endforeach
                </div>
                <x-slot:footer><div class="mt-2 flex items-center gap-2 border-t border-line pt-4"><span class="size-1.5 rounded-full bg-success"></span>Выполнено {{ count(array_filter($completedTasks)) }} из {{ count($tasks) }}</div></x-slot:footer>
            </x-panel>

            <x-panel title="Последние события">
                <ol class="space-y-5">
                    @foreach ([['ЕК', 'Екатерина обновила макеты', 'Forma Studio', '10 минут назад'], ['ДВ', 'Дмитрий завершил этап', 'Кино рядом', '45 минут назад'], ['МС', 'Мария добавила материалы', 'Botanica', '2 часа назад']] as [$initials, $event, $projectName, $time])
                        <li class="flex gap-3"><x-avatar :name="$teamNames[$initials]" size="sm" /><div><p class="text-sm leading-5">{{ $event }}</p><p class="mt-0.5 text-xs text-ink-muted">{{ $projectName }} <span class="px-1 text-ink-muted/40">·</span> {{ $time }}</p></div></li>
                    @endforeach
                </ol>
            </x-panel>
            <x-panel title="Баланс нагрузки" description="Комфортный темп для вашей команды">
                <x-progress :value="session('examples.settings.capacity', 75)" color="success" label="Плановая загрузка" show-value />
                <p class="mt-4 text-sm leading-5 text-ink-muted">Остальной ресурс доступен для новых задач. <a href="{{ route('examples.settings') }}#workspace" class="font-medium text-ink">Изменить предел</a></p>
            </x-panel>
        </div>
    </div>
@endsection
