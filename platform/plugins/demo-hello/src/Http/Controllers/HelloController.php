<?php

declare(strict_types=1);

namespace Plugins\DemoHello\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class HelloController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        /** @var object{content: string}|null $record */
        $record = DB::table('plugin_demo_messages')->latest('id')->first();

        $message = $record !== null ? $record->content : 'Hello from ACME Demo Plugin!';

        return view('demo-hello::hello', [
            'message' => $message,
        ]);
    }
}
