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
?>
<script defer src="<?= $assetPrefix ?>Assect/js/site-ajax.js"></script>
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
        <a class="relative material-symbols-outlined text-primary hover:opacity-80 transition-opacity active:scale-95 duration-200" data-icon="shopping_cart" href="<?= $navPrefix ?>cart.php">
            shopping_cart
            <?php if ($cartItemCount > 0): ?>
                <span class="absolute -top-2 -right-3 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-on-primary text-[10px] leading-[18px] text-center font-bold"><?= $cartItemCount > 99 ? '99+' : $cartItemCount ?></span>
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
