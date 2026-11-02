<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamplesController extends Controller
{
    public const STATUSES = ['active' => 'В работе', 'review' => 'На проверке', 'done' => 'Завершён', 'archived' => 'В архиве'];

    public const STATUS_COLORS = ['active' => 'brand', 'review' => 'info', 'done' => 'success', 'archived' => 'neutral'];

    public const TEAM = ['АМ' => 'Алексей Морозов', 'ЕК' => 'Екатерина Козлова', 'ДВ' => 'Дмитрий Волков', 'МС' => 'Мария Смирнова'];

    private function projects(Request $request): array
    {
        return $request->session()->get('examples.projects', [
            ['id' => 'nord', 'name' => 'Nord — цифровой банк', 'client' => 'Nord Finance', 'category' => 'Продуктовый дизайн', 'status' => 'active', 'progress' => 72, 'budget' => 480000, 'initials' => 'N', 'color' => 'brand', 'team' => ['АМ', 'ЕК', 'ДВ'], 'due' => '18 ноября'],
            ['id' => 'forma', 'name' => 'Новый взгляд на Forma', 'client' => 'Forma Studio', 'category' => 'Айдентика', 'status' => 'review', 'progress' => 94, 'budget' => 240000, 'initials' => 'F', 'color' => 'contrast', 'team' => ['ЕК', 'МС'], 'due' => '6 ноября'],
            ['id' => 'botanica', 'name' => 'Botanica Market', 'client' => 'Botanica', 'category' => 'Интернет-магазин', 'status' => 'active', 'progress' => 38, 'budget' => 320000, 'initials' => 'B', 'color' => 'success', 'team' => ['АМ', 'МС'], 'due' => '24 ноября'],
            ['id' => 'kino', 'name' => 'Кино рядом', 'client' => 'Kino', 'category' => 'Мобильное приложение', 'status' => 'active', 'progress' => 56, 'budget' => 560000, 'initials' => 'К', 'color' => 'info', 'team' => ['ДВ', 'ЕК', 'АМ'], 'due' => '21 ноября'],
            ['id' => 'sunday', 'name' => 'Sunday, каждый день', 'client' => 'Sunday Coffee', 'category' => 'Айдентика', 'status' => 'done', 'progress' => 100, 'budget' => 180000, 'initials' => 'S', 'color' => 'warning', 'team' => ['МС', 'ЕК'], 'due' => '30 октября'],
            ['id' => 'atlas', 'name' => 'Atlas Workspace', 'client' => 'Atlas', 'category' => 'Дизайн-система', 'status' => 'review', 'progress' => 86, 'budget' => 390000, 'initials' => 'A', 'color' => 'neutral', 'team' => ['АМ', 'ДВ'], 'due' => '10 ноября'],
        ]);
    }

    public function overview(Request $request): View
    {
        $projects = $this->projects($request);
        $tasks = [
            ['id' => 'brief', 'label' => 'Согласовать бриф Nord', 'project' => 'Nord Finance', 'time' => '11:30'],
            ['id' => 'review', 'label' => 'Проверить макеты Forma', 'project' => 'Forma Studio', 'time' => '14:00'],
            ['id' => 'prototype', 'label' => 'Отправить прототип клиенту', 'project' => 'Botanica', 'time' => '16:30'],
        ];

        return view('examples::overview', [
            'projects' => $projects,
            'activeCount' => count(array_filter($projects, fn ($p) => $p['status'] === 'active')),
            'reviewCount' => count(array_filter($projects, fn ($p) => $p['status'] === 'review')),
            'tasks' => $tasks,
            'completedTasks' => $request->session()->get('examples.tasks', []),
        ]);
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'recent');
        $projects = array_values(array_filter($this->projects($request), fn ($p) => ($query === '' || Str::contains(mb_strtolower($p['name'].' '.$p['client']), mb_strtolower($query)))
            && ($status === '' || $p['status'] === $status)));

        if ($sort === 'name') {
            usort($projects, fn ($a, $b) => strcmp($a['name'], $b['name']));
        } elseif ($sort === 'progress') {
            usort($projects, fn ($a, $b) => $b['progress'] <=> $a['progress']);
        }

        return view('examples::projects', compact('projects', 'query', 'status', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:100'],
            'client' => ['required', 'string', 'max:80'],
            'category' => ['required', Rule::in(['Продуктовый дизайн', 'Айдентика', 'Интернет-магазин', 'Мобильное приложение', 'Дизайн-система'])],
            'budget' => ['required', 'numeric', 'min:0', 'max:100000000'],
        ]);
        $projects = $this->projects($request);
        array_unshift($projects, [
            'id' => (string) Str::uuid(), 'name' => $data['project_name'], 'client' => $data['client'],
            'category' => $data['category'], 'budget' => (int) $data['budget'], 'status' => 'active',
            'progress' => 0, 'initials' => mb_strtoupper(mb_substr($data['project_name'], 0, 1)),
            'color' => 'brand', 'team' => ['АМ'], 'due' => 'Без срока',
        ]);
        $request->session()->put('examples.projects', $projects);

        return to_route('examples.projects')->with('notice', 'Проект создан. Можно приступать к работе.');
    }

    public function archive(Request $request): RedirectResponse
    {
        $ids = array_column($this->projects($request), 'id');
        $data = $request->validate(['projects' => ['required', 'array', 'min:1'], 'projects.*' => [Rule::in($ids)]]);
        $projects = array_map(function ($project) use ($data) {
            if (in_array($project['id'], $data['projects'], true)) {
                $project['status'] = 'archived';
            }

            return $project;
        }, $this->projects($request));
        $request->session()->put('examples.projects', $projects);

        return to_route('examples.projects')->with('notice', 'Выбранные проекты перемещены в архив.');
    }

    public function settings(Request $request): View
    {
        return view('examples::settings', ['settings' => $request->session()->get('examples.settings', [
            'name' => 'Алексей Морозов', 'email' => 'alex@atelier.design', 'workspace' => 'Atelier Studio',
            'timezone' => 'Europe/Moscow', 'language' => 'ru', 'visibility' => 'team',
            'email_updates' => true, 'mentions' => true, 'weekly_digest' => false,
            'capacity' => 75, 'compact' => false, 'tips' => true,
        ])]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:120'],
            'workspace' => ['required', 'string', 'max:80'],
            'timezone' => ['required', Rule::in(['Europe/Moscow', 'Europe/Astrakhan', 'Asia/Tbilisi', 'Europe/Berlin'])],
            'language' => ['required', Rule::in(['ru', 'en'])],
            'visibility' => ['required', Rule::in(['team', 'private'])],
            'capacity' => ['required', 'integer', 'between:25,100'],
        ]);
        foreach (['email_updates', 'mentions', 'weekly_digest', 'compact', 'tips'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $request->session()->put('examples.settings', $data);

        return to_route('examples.settings')->with('notice', 'Настройки сохранены. Всё готово к работе.');
    }

    public function task(Request $request, string $task): RedirectResponse
    {
        abort_unless(in_array($task, ['brief', 'review', 'prototype'], true), 404);
        $tasks = $request->session()->get('examples.tasks', []);
        $tasks[$task] = $request->boolean('completed');
        $request->session()->put('examples.tasks', $tasks);

        return to_route('examples.overview');
    }

    public function export(Request $request): Response
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Проект', 'Клиент', 'Статус', 'Готовность', 'Бюджет'], ';', '"', '');
        foreach ($this->projects($request) as $p) {
            // Keep spreadsheet programs from interpreting user-entered text as formulas.
            $safe = fn ($value) => preg_match('/^[=+@\-\t\r]/u', $value) ? "'".$value : $value;
            fputcsv($stream, [$safe($p['name']), $safe($p['client']), self::STATUSES[$p['status']], $p['progress'].'%', $p['budget']], ';', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="atelier-projects.csv"']);
    }
}
