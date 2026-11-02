<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\DynamicComponent;
use Tests\WorkbenchTestCase;

uses(WorkbenchTestCase::class);

beforeEach(function () {
    config(['session.driver' => 'array', 'malevich.components.prefix' => null, 'malevich.debug' => false]);
    $this->app['view']->addNamespace('demokit', dirname(__DIR__, 2).'/resources/kit');
    // Demo pages use the actual kit, not the intentionally tiny test fixtures.
    foreach (['button', 'panel', 'dialog', 'input', 'select', 'field', 'checkbox', 'radio', 'switch', 'slider', 'progress', 'badge', 'divider', 'avatar', 'heading', 'text', 'tabs'] as $component) {
        Blade::component('demokit::'.$component.'.index', $component);
    }
    Blade::component('demokit::button.group', 'button.group');
    foreach (['list', 'tab', 'panel'] as $part) {
        Blade::component('demokit::tabs.'.$part, 'tabs.'.$part);
    }
    (new ReflectionProperty(DynamicComponent::class, 'compiler'))->setValue(null, null);
    require dirname(__DIR__, 2).'/workbench/routes/web.php';
});

it('renders the complete workspace with Lucide icons', function (string $url, string $title) {
    $this->get($url)->assertOk()->assertSee($title)->assertSee('Malevich')
        ->assertSee('data-theme-toggle', false)->assertSee('<svg', false)->assertDontSee('wire:snapshot', false);
})->with([['/examples', 'Обзор пространства'], ['/examples/projects', 'Новый проект'], ['/examples/settings', 'Настройки пространства']]);

it('renders the requested icon component from the installed Lucide package', function () {
    $html = Blade::render('<x-icon name="lucide-user" class="shrink-0 size-4" />');
    expect($html)->toContain('<svg', 'shrink-0 size-4', 'viewBox="0 0 24 24"');
});

it('filters and sorts projects using native GET parameters', function () {
    $this->get('/examples/projects?q=Nord')->assertOk()->assertSee('Nord — цифровой банк')->assertDontSee('Botanica Market');
    $this->get('/examples/projects?status=done')->assertOk()->assertSee('Sunday, каждый день')->assertDontSee('Nord — цифровой банк');
    $this->get('/examples/projects?q=does-not-exist')->assertOk()->assertSee('Ничего не нашлось');
});

it('creates a project and keeps it in the workspace session', function () {
    $this->post('/examples/projects', ['project_name' => 'Aurora', 'client' => 'Aurora Studio', 'category' => 'Айдентика', 'budget' => 120000])
        ->assertRedirect('/examples/projects')->assertSessionHasNoErrors();
    $this->get('/examples/projects')->assertSee('Aurora Studio')->assertSee('Проект создан');
});

it('reopens the creation dialog on validation errors without inserting an invalid project', function () {
    $this->from('/examples/projects')->post('/examples/projects', ['project_name' => '', 'budget' => -10])
        ->assertRedirect('/examples/projects')->assertSessionHasErrors(['project_name', 'client', 'budget']);
    $this->get('/examples/projects')->assertOk()->assertSee('data-open-on-load', false);
});

it('archives only selected projects and rejects unknown ids', function () {
    $this->post('/examples/projects/archive', ['projects' => ['nord']])->assertRedirect('/examples/projects');
    $this->get('/examples/projects?status=archived')->assertSee('Nord — цифровой банк')->assertDontSee('Botanica Market');
    $this->post('/examples/projects/archive', ['projects' => ['missing']])->assertSessionHasErrors('projects.0');
});

it('saves valid settings and applies the visible workspace preferences', function () {
    $this->post('/examples/settings', [
        'name' => 'Мария Орлова', 'email' => 'maria@example.com', 'workspace' => 'North Studio',
        'timezone' => 'Europe/Astrakhan', 'language' => 'ru', 'visibility' => 'team', 'capacity' => 60,
        'mentions' => '1', 'compact' => '1', 'tips' => '0',
    ])->assertRedirect('/examples/settings')->assertSessionHasNoErrors()
        ->assertSessionHas('examples.settings.capacity', '60')->assertSessionHas('examples.settings.email_updates', false);
    $this->get('/examples/settings')->assertOk()->assertSee('Мария Орлова')->assertSee('data-density="compact"', false)->assertDontSee('Место для новых идей');
    $this->get('/examples')->assertOk()->assertSee('value="60"', false);
});

it('validates settings and toggles tasks without external side effects', function () {
    $this->post('/examples/settings', ['name' => '', 'email' => 'invalid'])->assertSessionHasErrors(['name', 'email']);
    $this->post('/examples/tasks/brief', ['completed' => '1'])->assertRedirect('/examples')->assertSessionHas('examples.tasks.brief', true);
    $this->post('/examples/tasks/brief', [])->assertSessionHas('examples.tasks.brief', false);
    $this->post('/examples/tasks/unknown')->assertNotFound();
});

it('exports a real CSV and neutralizes spreadsheet formulas in project names', function () {
    $this->post('/examples/projects', ['project_name' => '=1+1', 'client' => '@client', 'category' => 'Айдентика', 'budget' => 1000]);
    $response = $this->get('/examples/projects/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->getContent())->toContain("'=1+1", "'@client", 'Nord');
});
