<?php

use Tivents\CronMonitor\Listeners\CronListener;
use Illuminate\Console\Scheduling\Event;

describe('CronListener Helper Functions', function () {
    beforeEach(function () {
        $this->listener = new CronListener();
    });

    it('generiert konsistente Task-IDs', function () {
        $task1 = Mockery::mock(Event::class);
        $task1->command = 'test:command';

        $task2 = Mockery::mock(Event::class);
        $task2->command = 'test:command';

        config(['app.name' => 'test-app']);

        // Verwenden wir Reflection, um die private Methode zu testen
        $reflection = new ReflectionClass($this->listener);
        $method = $reflection->getMethod('generateTaskId');
        $method->setAccessible(true);

        $id1 = $method->invoke($this->listener, $task1);
        $id2 = $method->invoke($this->listener, $task2);

        expect($id1)->toBe($id2);
        expect($id1)->toBeString();
        expect(strlen($id1))->toBe(32); // MD5 Hash length
    });

    it('generiert verschiedene Task-IDs für verschiedene Commands', function () {
        $task1 = Mockery::mock(Event::class);
        $task1->command = 'test:command-one';

        $task2 = Mockery::mock(Event::class);
        $task2->command = 'test:command-two';

        config(['app.name' => 'test-app']);

        $reflection = new ReflectionClass($this->listener);
        $method = $reflection->getMethod('generateTaskId');
        $method->setAccessible(true);

        $id1 = $method->invoke($this->listener, $task1);
        $id2 = $method->invoke($this->listener, $task2);

        expect($id1)->not->toBe($id2);
    });

    it('berücksichtigt App-Name in Task-ID Generation', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $reflection = new ReflectionClass($this->listener);
        $method = $reflection->getMethod('generateTaskId');
        $method->setAccessible(true);

        config(['app.name' => 'app-one']);
        $id1 = $method->invoke($this->listener, $task);

        config(['app.name' => 'app-two']);
        $id2 = $method->invoke($this->listener, $task);

        expect($id1)->not->toBe($id2);
    });
});