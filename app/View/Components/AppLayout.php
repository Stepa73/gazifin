<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(
        /** Položka spodní navigace, která se má na mobilu zvýraznit. */
        public ?string $active = null,
        /** Titulek mobilní hlavičky. Bez něj se hlavička nevykreslí. */
        public ?string $title = null,
        /** Cíl šipky zpět v mobilní hlavičce. */
        public ?string $backUrl = null,
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
