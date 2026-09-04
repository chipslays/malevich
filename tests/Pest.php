<?php

use Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        $this->resetMalevichRegistry();
    })
    ->in('Unit', 'Feature');
