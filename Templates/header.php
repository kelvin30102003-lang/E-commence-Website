<?php
$activePage = $activePage ?? '';
$navPrefix = $navPrefix ?? '';
$assetPrefix = $assetPrefix ?? '../';
$authLoginHref = $authLoginHref ?? '../Auth/login.php';
$baseLinkClass = 'text-sm md:text-base font-semibold font-label-md text-label-md hover:opacity-80 transition-opacity';
$activeLinkClass = 'text-primary dark:text-primary-fixed-dim';
$inactiveLinkClass = 'text-on-surface-variant dark:text-on-surface-variant';
require_once __DIR__ . '/firebase_bootstrap.php';
$cartItemCount = function_exists('shop_cart_item_count') ? max(0, (int)shop_cart_item_count()) : 0;
$profileIsActive = $activePage === 'profile';
$cartDrawerAttribute = $activePage === 'cart' ? '' : ' data-cart-drawer-trigger';
?>
<script defer src="<?= $assetPrefix ?>Assect/js/site-ajax.js"></script>
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

    .cart-drawer-shell {
        position: fixed;
        inset: 0;
        z-index: 80;
        pointer-events: none;
    }

    .cart-drawer-shell.is-open {
        pointer-events: auto;
    }

    .cart-drawer-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(27, 28, 28, 0.34);
        opacity: 0;
        transition: opacity 260ms ease;
    }

    .cart-drawer-shell.is-open .cart-drawer-backdrop {
        opacity: 1;
    }

    .cart-drawer-panel {
        position: absolute;
        top: 0;
        right: 0;
        width: min(560px, 100vw);
        height: 100%;
        background: #ffffff;
        box-shadow: -18px 0 48px rgba(27, 28, 28, 0.16);
        transform: translateX(100%);
        transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
        display: flex;
        flex-direction: column;
    }

    .cart-drawer-shell.is-open .cart-drawer-panel {
        transform: translateX(0);
    }

    .cart-drawer-frame {
        width: 100%;
        flex: 1;
        border: 0;
        background: #fbf9f8;
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
<header class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-4 md:px-6 px-gutter h-16 bg-surface dark:bg-surface shadow-sm opacity-90">
    <div class="flex items-center gap-4 gap-md">
        <span class="material-symbols-outlined text-primary" data-icon="bubble_chart">bubble_chart</span>
        <span class="text-xl md:text-2xl text-headline-md font-headline-md font-bold text-primary dark:text-primary-fixed-dim tracking-tight">LuvShop</span>
    </div>
    <nav class="hidden md:flex items-center gap-8 gap-xl">
        <a class="<?= ($activePage === 'home' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass ?>" data-ajax="true" href="<?= $navPrefix ?>Home.php">Home</a>
        <a class="<?= ($activePage === 'shop' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass ?>" data-ajax="true" href="<?= $navPrefix ?>shop.php">Shop</a>
        <a class="<?= ($activePage === 'contact' ? $activeLinkClass : $inactiveLinkClass) . ' ' . $baseLinkClass ?>" data-ajax="true" href="<?= $navPrefix ?>contactUs.php">Contact</a>
    </nav>
    <div class="flex items-center gap-4 gap-md">
        <a class="relative material-symbols-outlined text-primary hover:opacity-80 transition-opacity active:scale-95 duration-200"<?= $cartDrawerAttribute ?> data-icon="shopping_cart" href="<?= $navPrefix ?>cart.php" data-cart-link>
            shopping_cart
            <?php if ($cartItemCount > 0): ?>
                <span class="absolute -top-2 -right-3 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-on-primary text-[10px] leading-[18px] text-center font-bold" data-cart-count-badge><?= $cartItemCount > 99 ? '99+' : $cartItemCount ?></span>
            <?php endif; ?>
        </a>
        <a
            id="profile-link"
            class="w-10 h-10 rounded-full border flex items-center justify-center overflow-hidden transition-all <?= $profileIsActive ? 'border-primary bg-primary-container text-primary' : 'border-outline-variant/40 bg-surface-container-low text-on-surface-variant hover:text-primary hover:border-primary/40' ?>"
            href="<?= $navPrefix ?>profile.php"
            title="Profile"
        >
            <img id="profile-avatar-image" alt="Profile" class="hidden w-full h-full object-cover" src=""/>
            <span id="profile-avatar-icon" class="material-symbols-outlined" data-icon="person">person</span>
        </a>
        <a id="auth-link" class="hidden md:inline-flex md:mr-2 <?= $inactiveLinkClass . ' ' . $baseLinkClass ?>" data-ajax="true" data-login-href="<?= $authLoginHref ?>" href="<?= $authLoginHref ?>">Login</a>
    </div>
</header>
<div class="cart-drawer-shell" data-cart-drawer aria-hidden="true">
    <div class="cart-drawer-backdrop" data-cart-drawer-close></div>
    <aside class="cart-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title">
        <div class="h-[100px] flex items-center justify-between border-b border-outline-variant/30 px-8">
            <h2 id="cart-drawer-title" class="font-headline-md text-[26px] font-bold leading-none text-on-surface">Your Cart</h2>
            <button class="material-symbols-outlined text-[36px] text-on-surface transition-opacity hover:opacity-70" data-cart-drawer-close type="button" aria-label="Close cart">
                close
            </button>
        </div>
        <iframe class="cart-drawer-frame" data-cart-drawer-frame title="Your Cart"></iframe>
    </aside>
</div>
<script>
    (() => {
        const drawer = document.querySelector("[data-cart-drawer]");
        const frame = drawer ? drawer.querySelector("[data-cart-drawer-frame]") : null;
        const closeButtons = drawer ? drawer.querySelectorAll("[data-cart-drawer-close]") : [];

        if (!drawer || !frame) {
            return;
        }

        const drawerUrl = (href) => {
            const url = new URL(href, window.location.href);
            url.searchParams.set("drawer", "1");
            return url.toString();
        };

        const closeCartPopups = () => {
            document.querySelectorAll("[data-cart-popup]").forEach((popup) => {
                popup.remove();
            });
        };

        const updateCartCount = (count) => {
            const normalizedCount = Math.max(0, parseInt(String(count), 10) || 0);
            const cartLink = document.querySelector("[data-cart-link]");
            if (!cartLink) {
                return;
            }

            let badge = cartLink.querySelector("[data-cart-count-badge]");
            if (normalizedCount <= 0) {
                if (badge) {
                    badge.remove();
                }
                closeCartPopups();
                return;
            }

            if (!badge) {
                badge = document.createElement("span");
                badge.dataset.cartCountBadge = "true";
                badge.className = "absolute -top-2 -right-3 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-on-primary text-[10px] leading-[18px] text-center font-bold";
                cartLink.appendChild(badge);
            }

            badge.textContent = normalizedCount > 99 ? "99+" : String(normalizedCount);
        };

        const openDrawer = (href) => {
            closeCartPopups();
            frame.src = drawerUrl(href);
            drawer.classList.add("is-open");
            drawer.setAttribute("aria-hidden", "false");
            document.documentElement.style.overflow = "hidden";
        };

        const closeDrawer = () => {
            drawer.classList.remove("is-open");
            drawer.setAttribute("aria-hidden", "true");
            document.documentElement.style.overflow = "";
        };

        document.addEventListener("click", (event) => {
            const target = event.target instanceof Element ? event.target : null;
            const trigger = target ? target.closest("[data-cart-drawer-trigger]") : null;
            if (!trigger || !(trigger instanceof HTMLAnchorElement)) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            openDrawer(trigger.href);
        });

        closeButtons.forEach((button) => {
            button.addEventListener("click", closeDrawer);
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && drawer.classList.contains("is-open")) {
                closeDrawer();
            }
        });

        window.addEventListener("message", (event) => {
            if (event.origin !== window.location.origin || !event.data || event.data.type !== "luvshop:cart-count") {
                return;
            }

            updateCartCount(event.data.count);
        });
    })();
</script>
