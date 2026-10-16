<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Contracts\HookSubscriberInterface;
use App\Core\Hooks\Facades\Hook;
use App\Core\Hooks\HookManager;
use Tests\TestCase;

class TestHookSubscriber implements HookSubscriberInterface
{
    /** @var list<string> */
    public array $log = [];

    /**
     * @return array{
     *     actions?: array<string, string|array{0: string, 1?: int}|list<string|array{0: string, 1?: int}>>,
     *     filters?: array<string, string|array{0: string, 1?: int}|list<string|array{0: string, 1?: int}>>
     * }
     */
    public function getSubscribedHooks(): array
    {
        return [
            'actions' => [
                'test.subscriber.action' => [
                    ['secondAction', 20],
                    ['firstAction', 5],
                ],
                'test.simple.action' => 'simpleAction',
            ],
            'filters' => [
                'test.subscriber.filter' => ['filterText', 10],
            ],
        ];
    }

    public function firstAction(): void
    {
        $this->log[] = 'first';
    }

    public function secondAction(): void
    {
        $this->log[] = 'second';
    }

    public function simpleAction(): void
    {
        $this->log[] = 'simple';
    }

    public function filterText(string $value): string
    {
        return "[{$value}]";
    }
}

class HookManagerTest extends TestCase
{
    private HookManager $hooks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hooks = new HookManager;
    }

    public function test_action_is_executed_when_do_action_is_called(): void
    {
        $called = false;
        $receivedArgs = [];

        $this->hooks->addAction('test.action', function (string $arg1, int $arg2) use (&$called, &$receivedArgs): void {
            $called = true;
            $receivedArgs = [$arg1, $arg2];
        });

        $this->hooks->doAction('test.action', 'hello', 42);

        $this->assertTrue($called);
        $this->assertSame(['hello', 42], $receivedArgs);
    }

    public function test_actions_are_executed_in_order_of_priority(): void
    {
        $executionOrder = [];

        // Register action with priority 20
        $this->hooks->addAction('content.saved', function () use (&$executionOrder): void {
            $executionOrder[] = 'priority-20';
        }, 20);

        // Register action with priority 10
        $this->hooks->addAction('content.saved', function () use (&$executionOrder): void {
            $executionOrder[] = 'priority-10';
        }, 10);

        // Register action with priority 30
        $this->hooks->addAction('content.saved', function () use (&$executionOrder): void {
            $executionOrder[] = 'priority-30';
        }, 30);

        $this->hooks->doAction('content.saved');

        $this->assertSame(['priority-10', 'priority-20', 'priority-30'], $executionOrder);
    }

    public function test_actions_with_same_priority_execute_in_fifo_order(): void
    {
        $executionOrder = [];

        $this->hooks->addAction('menu.render', function () use (&$executionOrder): void {
            $executionOrder[] = 'first-added';
        }, 10);

        $this->hooks->addAction('menu.render', function () use (&$executionOrder): void {
            $executionOrder[] = 'second-added';
        }, 10);

        $this->hooks->doAction('menu.render');

        $this->assertSame(['first-added', 'second-added'], $executionOrder);
    }

    public function test_filter_mutates_value_through_ordered_pipeline(): void
    {
        // Priority 20: append "!"
        $this->hooks->addFilter('content.title', function (string $title): string {
            return $title.'!';
        }, 20);

        // Priority 10: append " World"
        $this->hooks->addFilter('content.title', function (string $title): string {
            return $title.' World';
        }, 10);

        $result = $this->hooks->applyFilter('content.title', 'Hello');

        $this->assertSame('Hello World!', $result);
    }

    public function test_filter_returns_original_value_when_no_filters_registered(): void
    {
        $result = $this->hooks->applyFilter('unregistered.filter', 'Initial Value');

        $this->assertSame('Initial Value', $result);
    }

    public function test_filter_passes_additional_context_arguments(): void
    {
        $this->hooks->addFilter('format.price', function (float $amount, string $currency, int $decimals): string {
            return number_format($amount, $decimals).' '.$currency;
        });

        $formatted = $this->hooks->applyFilter('format.price', 99.95, 'EUR', 2);

        $this->assertSame('99.95 EUR', $formatted);
    }

    public function test_has_action_and_has_filter(): void
    {
        $callback = fn () => null;

        $this->assertFalse($this->hooks->hasAction('custom.action'));
        $this->assertFalse($this->hooks->hasFilter('custom.filter'));

        $this->hooks->addAction('custom.action', $callback);
        $this->hooks->addFilter('custom.filter', $callback);

        $this->assertTrue($this->hooks->hasAction('custom.action'));
        $this->assertTrue($this->hooks->hasAction('custom.action', $callback));
        $this->assertFalse($this->hooks->hasAction('custom.action', fn () => null));

        $this->assertTrue($this->hooks->hasFilter('custom.filter'));
        $this->assertTrue($this->hooks->hasFilter('custom.filter', $callback));
    }

    public function test_remove_action_and_remove_filter(): void
    {
        $log = [];
        $cb1 = function () use (&$log): void {
            $log[] = 'cb1';
        };
        $cb2 = function () use (&$log): void {
            $log[] = 'cb2';
        };

        $this->hooks->addAction('test.remove', $cb1, 10);
        $this->hooks->addAction('test.remove', $cb2, 20);

        $this->hooks->removeAction('test.remove', $cb1, 10);
        $this->hooks->doAction('test.remove');

        $this->assertSame(['cb2'], $log);

        // Remove entire hook
        $this->hooks->removeAction('test.remove');
        $this->assertFalse($this->hooks->hasAction('test.remove'));
    }

    public function test_remove_all_clears_hook_listeners(): void
    {
        $this->hooks->addAction('shared.hook', fn () => null);
        $this->hooks->addFilter('shared.hook', fn ($v) => $v);

        $this->assertTrue($this->hooks->hasAction('shared.hook'));
        $this->assertTrue($this->hooks->hasFilter('shared.hook'));

        $this->hooks->removeAll('shared.hook');

        $this->assertFalse($this->hooks->hasAction('shared.hook'));
        $this->assertFalse($this->hooks->hasFilter('shared.hook'));
    }

    public function test_hook_subscriber_registers_actions_and_filters_declaratively(): void
    {
        $subscriber = new TestHookSubscriber;
        $this->hooks->addSubscriber($subscriber);

        $this->hooks->doAction('test.subscriber.action');
        $this->assertSame(['first', 'second'], $subscriber->log);

        $this->hooks->doAction('test.simple.action');
        $this->assertSame(['first', 'second', 'simple'], $subscriber->log);

        $filtered = $this->hooks->applyFilter('test.subscriber.filter', 'hello');
        $this->assertSame('[hello]', $filtered);
    }

    public function test_hook_facade_resolves_and_executes_actions_and_filters(): void
    {
        Hook::flush();

        $actionExecuted = false;
        Hook::addAction('facade.action', function () use (&$actionExecuted): void {
            $actionExecuted = true;
        });

        Hook::doAction('facade.action');
        $this->assertTrue($actionExecuted);

        Hook::addFilter('facade.filter', function (string $text): string {
            return strtoupper($text);
        });

        $result = Hook::applyFilter('facade.filter', 'hello facade');
        $this->assertSame('HELLO FACADE', $result);
    }
}
