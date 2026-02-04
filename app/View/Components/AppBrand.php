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
     * Brand tagline size class (e.g., text-xl, text-2xl).
     */
    public string $taglineSize;

    

    /**
     * Create a new component instance.
     */
    public function __construct(string $iconWidth = 'w-6', string $textSize = 'text-xl', string $taglineSize = 'text-sm')
    {
        $this->iconWidth = $iconWidth;
        $this->textSize = $textSize;
        $this->taglineSize = $taglineSize;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="{{ route('home') }}">
                    <!-- Hidden when collapsed -->
                    <div {{ $attributes->class(["hidden-when-collapsed"]) }}>
                        <div class="flex items-start gap-2 w-fit p-2">
                            <x-icon name="o-cube" class="{{ $iconWidth }} text-teal-600 dark:text-teal-200" />
                            <div class="relative flex flex-col gap-0">
                                <div class="flex gap-0">
                                    <span class="font-bold tracking-wider {{ $textSize }} bg-linear-to-r from-teal-400 to-teal-700 dark:from-teal-600 dark:to-teal-300 bg-clip-text text-transparent">Rent</span>
                                    <span class="font-bold tracking-wider {{ $textSize }} bg-linear-to-l from-teal-400 to-teal-700 dark:from-teal-600 dark:to-teal-300 bg-clip-text text-transparent">ory</span>
                                </div>
                                <p class="{{ $taglineSize }} line-clamp-1 bg-[radial-gradient(ellipse_75%_50%_at_50%_50%,var(--tw-gradient-from),var(--tw-gradient-to))] from-teal-700 to-teal-400 dark:from-teal-200 dark:to-teal-600 bg-clip-text text-transparent">Manage Rental Business with Ease</p>
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
