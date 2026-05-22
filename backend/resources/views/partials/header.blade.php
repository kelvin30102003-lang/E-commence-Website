@php
$activePage = $activePage ?? '';
$baseLinkClass = 'font-label-md text-label-md hover:opacity-80 transition-opacity';
$activeLinkClass = 'text-primary dark:text-primary-fixed-dim';
$inactiveLinkClass = 'text-on-surface-variant dark:text-on-surface-variant';
@endphp
<header class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-gutter h-16 bg-surface dark:bg-surface shadow-sm opacity-90">
    <div class="flex items-center gap-md">
        <span class="material-symbols-outlined text-primary" data-icon="bubble_chart">bubble_chart</span>
        <span class="text-headline-md font-headline-md font-bold text-primary dark:text-primary-fixed-dim tracking-tight">LuvShop</span>
    </div>
    <nav class="hidden md:flex items-center gap-xl">
        <a class="{{ ($activePage === 'home' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass }}" href="{{ route('home') }}">Home</a>
        <a class="{{ ($activePage === 'shop' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass }}" href="{{ route('shop') }}">Shop</a>
        <a class="{{ ($activePage === 'contact' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass }}" href="{{ route('contact') }}">Contact</a>
        <a class="{{ $inactiveLinkClass . ' ' . $baseLinkClass }}" href="#">Profile</a>
    </nav>
    <div class="flex items-center gap-md">
        <button class="material-symbols-outlined text-primary hover:opacity-80 transition-opacity active:scale-95 duration-200" data-icon="shopping_cart">shopping_cart</button>
        <a class="hidden md:inline-flex md:mr-2 {{ $inactiveLinkClass . ' ' . $baseLinkClass }}" href="#">Login</a>
    </div>
</header>
