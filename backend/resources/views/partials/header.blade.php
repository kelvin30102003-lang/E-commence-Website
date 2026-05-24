@php
$activePage = $activePage ?? '';
$baseLinkClass = 'font-label-md text-label-md hover:opacity-80 transition-opacity';
$activeLinkClass = 'text-primary dark:text-primary-fixed-dim';
$inactiveLinkClass = 'text-on-surface-variant dark:text-on-surface-variant';
@endphp
<style>
    .luvshop-page-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: grid;
        place-items: center;
        background:
            radial-gradient(circle at 50% 42%, rgba(255, 209, 220, 0.58), transparent 28rem),
            linear-gradient(135deg, #fbf9f8 0%, #f5f3f3 46%, #e9ddab 100%);
        color: #78555e;
        transition: opacity 420ms ease, visibility 420ms ease;
    }

    .dark .luvshop-page-loader {
        background:
            radial-gradient(circle at 50% 42%, rgba(231, 187, 198, 0.18), transparent 28rem),
            linear-gradient(135deg, #303030 0%, #1b1c1c 48%, #665f36 100%);
        color: #e7bbc6;
    }

    .luvshop-page-loader.is-loaded {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .luvshop-loader-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .luvshop-loader-mark {
        width: 4rem;
        height: 4rem;
        display: grid;
        place-items: center;
        border-radius: 9999px;
        background: #ffd1dc;
        color: #78555e;
        box-shadow: 0 20px 45px rgba(120, 85, 94, 0.18);
        animation: luvshop-mark-pop 1200ms ease-in-out infinite;
    }

    .dark .luvshop-loader-mark {
        background: #e7bbc6;
        color: #2d141c;
    }

    .luvshop-loader-word {
        display: flex;
        align-items: baseline;
        justify-content: center;
        min-height: 3.25rem;
        font-family: "Quicksand", sans-serif;
        font-size: clamp(2.25rem, 10vw, 4.75rem);
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0;
    }

    .luvshop-loader-word span {
        display: inline-block;
        opacity: 0;
        transform: translateY(1rem) scale(0.78);
        animation: luvshop-letter-jump 780ms cubic-bezier(0.22, 1, 0.36, 1) both;
        animation-delay: calc(var(--letter-index) * 110ms);
    }

    .luvshop-loader-bar {
        width: min(13rem, 54vw);
        height: 0.25rem;
        overflow: hidden;
        border-radius: 9999px;
        background: rgba(120, 85, 94, 0.16);
    }

    .luvshop-loader-bar::before {
        content: "";
        display: block;
        width: 42%;
        height: 100%;
        border-radius: inherit;
        background: #78555e;
        animation: luvshop-loader-bar 1300ms ease-in-out infinite;
    }

    .dark .luvshop-loader-bar {
        background: rgba(231, 187, 198, 0.2);
    }

    .dark .luvshop-loader-bar::before {
        background: #e7bbc6;
    }

    @keyframes luvshop-letter-jump {
        0% {
            opacity: 0;
            transform: translateY(1rem) scale(0.78);
        }
        58% {
            opacity: 1;
            transform: translateY(-0.38rem) scale(1.08);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes luvshop-mark-pop {
        0%,
        100% {
            transform: scale(1);
        }
        45% {
            transform: scale(1.08);
        }
    }

    @keyframes luvshop-loader-bar {
        0% {
            transform: translateX(-110%);
        }
        100% {
            transform: translateX(245%);
        }
    }
</style>
<div id="luvshop-page-loader" class="luvshop-page-loader" role="status" aria-live="polite" aria-label="Loading LuvShop">
    <div class="luvshop-loader-card">
        <div class="luvshop-loader-mark" aria-hidden="true">
            <span class="material-symbols-outlined text-[34px]" data-icon="bubble_chart">bubble_chart</span>
        </div>
        <div class="luvshop-loader-word" aria-hidden="true">
            <span style="--letter-index: 0">L</span>
            <span style="--letter-index: 1">u</span>
            <span style="--letter-index: 2">v</span>
            <span style="--letter-index: 3">S</span>
            <span style="--letter-index: 4">h</span>
            <span style="--letter-index: 5">o</span>
            <span style="--letter-index: 6">p</span>
        </div>
        <div class="luvshop-loader-bar" aria-hidden="true"></div>
    </div>
</div>
<script>
    (() => {
        const loader = document.getElementById("luvshop-page-loader");
        if (!loader) {
            return;
        }

        const startedAt = performance.now();
        const minVisibleMs = 1500;
        let isHiding = false;

        const hideLoader = () => {
            if (isHiding) {
                return;
            }

            isHiding = true;
            const remainingTime = Math.max(0, minVisibleMs - (performance.now() - startedAt));
            window.setTimeout(() => {
                loader.classList.add("is-loaded");
                window.setTimeout(() => {
                    loader.remove();
                }, 460);
            }, remainingTime);
        };

        if (document.readyState === "complete") {
            hideLoader();
        } else {
            window.addEventListener("load", hideLoader, { once: true });
            window.setTimeout(hideLoader, 3600);
        }
    })();
</script>
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
