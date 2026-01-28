<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    /**
     * Icon width class (e.g., w-6, w-8).
     */
    public string $iconWidth;

    /**
     * Brand text size class (e.g., text-xl, text-2xl).
     */
    public string $textSize;

    /**
     * Create a new component instance.
     */
    public function __construct(string $iconWidth = 'w-6', string $textSize = 'text-xl')
    {
        $this->iconWidth = $iconWidth;
        $this->textSize = $textSize;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="/" wire:navigate>
                    <!-- Hidden when collapsed -->
                    <div {{ $attributes->class(["hidden-when-collapsed"]) }}>
                        <div class="flex items-center gap-2 w-fit p-2">
                            <x-icon name="o-cube" class="{{ $iconWidth }} text-teal-600 dark:text-teal-200" />
                            <div class="flex gap-0">
                                <span class="font-bold tracking-wider {{ $textSize }} bg-linear-to-r from-teal-400 to-teal-700 dark:from-teal-600 dark:to-teal-300 bg-clip-text text-transparent">Rent</span>
                                <span class="font-bold tracking-wider {{ $textSize }} bg-linear-to-l from-teal-400 to-teal-700 dark:from-teal-600 dark:to-teal-300 bg-clip-text text-transparent">ory</span>
                            </div>
                        </div>
                    </div>

                    <!-- Display when collapsed -->
                    <div class="display-when-collapsed hidden mx-5 mt-5 mb-1 h-[28px]">
                        <x-icon name="s-cube" class="w-6 -mb-1.5 text-purple-500" />
                    </div>
                </a>
            HTML;
    }
}
