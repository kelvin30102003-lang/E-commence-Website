<?php
$activePage = '';
$navPrefix = '../Users/';
$authLoginHref = 'login.php';
$redirectParam = trim((string)($_GET['redirect'] ?? ''));
$authRedirectQuery = $redirectParam !== '' ? '?redirect=' . rawurlencode($redirectParam) : '';
?>

<!DOCTYPE html>

<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary-fixed": "#b2f2bb",
                        "background": "#fbf9f8",
                        "on-secondary-container": "#357044",
                        "on-secondary-fixed-variant": "#145129",
                        "secondary": "#2f6a3f",
                        "inverse-on-surface": "#f2f0f0",
                        "outline": "#817476",
                        "on-tertiary": "#ffffff",
                        "on-surface": "#1b1c1c",
                        "surface-tint": "#78555e",
                        "on-primary-fixed": "#2d141c",
                        "error": "#ba1a1a",
                        "tertiary-container": "#e9ddab",
                        "surface-dim": "#dbd9d9",
                        "on-primary-fixed-variant": "#5e3e47",
                        "secondary-container": "#b2f2bb",
                        "on-tertiary-fixed": "#211c00",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary": "#ffffff",
                        "on-error": "#ffffff",
                        "tertiary-fixed": "#efe3b0",
                        "surface-container-highest": "#e4e2e2",
                        "on-primary-container": "#7a5761",
                        "primary-fixed": "#ffd9e2",
                        "surface-container-low": "#f5f3f3",
                        "surface-bright": "#fbf9f8",
                        "tertiary-fixed-dim": "#d2c796",
                        "tertiary": "#665f36",
                        "on-primary": "#ffffff",
                        "on-tertiary-fixed-variant": "#4e4721",
                        "error-container": "#ffdad6",
                        "surface-variant": "#e4e2e2",
                        "inverse-surface": "#303030",
                        "on-tertiary-container": "#696139",
                        "on-surface-variant": "#4f4446",
                        "inverse-primary": "#e7bbc6",
                        "primary-container": "#ffd1dc",
                        "surface": "#fbf9f8",
                        "outline-variant": "#d3c3c5",
                        "on-error-container": "#93000a",
                        "surface-container": "#efeded",
                        "secondary-fixed-dim": "#96d5a0",
                        "primary-fixed-dim": "#e7bbc6",
                        "on-secondary-fixed": "#00210b",
                        "surface-container-high": "#eae8e7",
                        "primary": "#78555e",
                        "on-background": "#1b1c1c"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "unit": "4px",
                        "margin-mobile": "20px",
                        "margin-desktop": "80px",
                        "xs": "4px",
                        "lg": "24px",
                        "gutter": "16px",
                        "xl": "48px",
                        "sm": "8px",
                        "md": "16px"
                    },
                    "fontFamily": {
                        "headline-md": ["Quicksand"],
                        "headline-lg": ["Quicksand"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Quicksand"],
                        "display-lg": ["Quicksand"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["24px", {
                            "lineHeight": "32px",
                            "fontWeight": "600"
                        }],
                        "headline-lg": ["32px", {
                            "lineHeight": "40px",
                            "fontWeight": "700"
                        }],
                        "body-lg": ["18px", {
                            "lineHeight": "28px",
                            "fontWeight": "400"
                        }],
                        "body-md": ["16px", {
                            "lineHeight": "24px",
                            "fontWeight": "400"
                        }],
                        "headline-lg-mobile": ["24px", {
                            "lineHeight": "32px",
                            "fontWeight": "700"
                        }],
                        "display-lg": ["48px", {
                            "lineHeight": "56px",
                            "letterSpacing": "-0.02em",
                            "fontWeight": "700"
                        }],
                        "label-sm": ["12px", {
                            "lineHeight": "16px",
                            "fontWeight": "700"
                        }],
                        "label-md": ["14px", {
                            "lineHeight": "20px",
                            "letterSpacing": "0.01em",
                            "fontWeight": "600"
                        }]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body {
            background-color: #fbf9f8;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .cute-shadow {
            box-shadow: 0 20px 40px -10px rgba(120, 85, 94, 0.12);
        }

        .spring-btn:active {
            transform: scale(0.95);
        }

        .glow-focus:focus-within {
            box-shadow: 0 0 0 4px rgba(255, 209, 220, 0.4);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col selection:bg-primary-container selection:text-primary">
    <?php require_once __DIR__ . '/../Templates/header.php'; ?>
<!-- Main Content Canvas -->
    <main class="flex-grow flex items-center justify-center pt-24 pb-12 px-gutter" id="app-main">
        <div class="w-full max-w-[1280px] flex flex-col lg:flex-row items-center justify-center gap-xl">
            <!-- Hero Illustration/Side Content (Desktop Only) -->
            <div class="hidden lg:flex flex-col gap-md w-1/2">
                <div class="relative w-full aspect-square rounded-xl overflow-hidden cute-shadow">
                    <img class="w-full h-full object-cover" data-alt="A whimsical and soft-focus lifestyle photograph of a cozy living space filled with pastel-toned decor items and plush cushions. The lighting is bright and warm, creating a high-key, dreamy atmosphere that aligns with a boutique shopping experience. Soft pink and lemon yellow accents are visible in the background, matching the brand color palette. The overall mood is domestic, safe, and inviting." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDJFbW_dhsiaLyx4tnUAfeeMiIhFjN-K1EJE2m7ZBgILY408IAxiQLsrIHfduvaYNRAAoIaFkuCGNxfmwzdQPeejHzmlPIbsCoY5j1Q10sNt-X_13aEvmohuMr0wFEOVA1wymzo17BcXh4yHXfzPVm0eU_e7YZLsnUoYB4W0x5wbii_02wuAxLJlL1A_cBTWG-onwyypYV-IT-y4jQuXji-p7UkQ0FoKZM6fowI2RQTXK38BWOSPhhQvjKEwBE8vbbXT8s0yvM7lTY" />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/30 to-transparent"></div>
                </div>
                <div class="px-md">
                    <h2 class="text-display-lg font-headline-lg text-primary">Discover items you'll fall in love with.</h2>
                    <p class="text-body-lg font-body-lg text-on-surface-variant mt-sm">Your cozy corner of the internet for all things cute and functional.</p>
                </div>
            </div>
            <!-- Login Card -->
            <div class="w-full max-w-md bg-surface-container-lowest rounded-lg p-xl cute-shadow border border-outline-variant/20">
                <div class="text-center mb-xl">
                    <h2 class="text-headline-lg font-headline-lg text-primary mb-sm">Sign In</h2>
                    <p class="text-body-md font-body-md text-on-surface-variant">Welcome back! Please enter your details.</p>
                </div>
                <form id="firebase-login-form" class="space-y-lg">
                    <!-- Email Field -->
                    <div class="space-y-xs">
                        <label class="text-label-md font-label-md text-on-surface-variant px-sm">Email Address</label>
                        <div class="glow-focus flex items-center bg-surface-container-low rounded-full px-md h-14 border-2 border-transparent focus-within:border-primary transition-all duration-300">
                            <span class="material-symbols-outlined text-outline mr-sm" data-icon="mail">mail</span>
                            <input id="firebase-login-email" class="bg-transparent border-none focus:ring-0 w-full text-body-md font-body-md text-on-surface placeholder:text-outline/60" placeholder="hello@luvshop.com" type="email" required />
                        </div>
                    </div>
                    <!-- Password Field -->
                    <div class="space-y-xs">
                        <div class="flex justify-between items-center px-sm">
                            <label class="text-label-md font-label-md text-on-surface-variant">Password</label>
                            <a class="text-label-sm font-label-sm text-primary hover:underline" href="#">Forgot password?</a>
                        </div>
                        <div class="glow-focus flex items-center bg-surface-container-low rounded-full px-md h-14 border-2 border-transparent focus-within:border-primary transition-all duration-300">
                            <span class="material-symbols-outlined text-outline mr-sm" data-icon="lock">lock</span>
                            <input id="firebase-login-password" class="bg-transparent border-none focus:ring-0 w-full text-body-md font-body-md text-on-surface placeholder:text-outline/60" placeholder="********" type="password" minlength="6" required />
                            <span class="material-symbols-outlined text-outline cursor-pointer hover:text-primary transition-colors" data-icon="visibility">visibility</span>
                        </div>
                    </div>
                    <p id="firebase-login-feedback" class="min-h-[24px] text-body-md"></p>
                    <!-- Action Button -->
                    <button class="spring-btn w-full h-14 bg-primary text-on-primary font-bold rounded-full text-body-lg shadow-lg shadow-primary/20 hover:opacity-90 transition-all duration-200" type="submit">
                        Login
                    </button>
                    <!-- Divider -->
                    <div class="flex items-center gap-md py-sm">
                        <div class="h-px flex-grow bg-outline-variant/30"></div>
                        <span class="text-label-sm font-label-sm text-outline uppercase tracking-widest">or continue with</span>
                        <div class="h-px flex-grow bg-outline-variant/30"></div>
                    </div>
                    <!-- Social Logins -->
                    <div class="flex gap-md">
                        <button class="spring-btn flex-1 h-14 border-2 border-outline-variant/30 rounded-full flex items-center justify-center gap-sm hover:bg-surface-container transition-colors" data-google-auth="login" type="button">
                            <span class="material-symbols-outlined text-on-surface" data-icon="google">google</span>
                            <span class="text-label-md font-label-md text-on-surface">Google</span>
                        </button>
                        
                    </div>
                    <!-- Register Link -->
                    <p class="text-center text-body-md font-body-md text-on-surface-variant mt-lg">
                        Don't have an account?
                        <a class="text-primary font-bold hover:underline decoration-2 underline-offset-4" data-ajax="true" href="register.php<?= htmlspecialchars($authRedirectQuery, ENT_QUOTES, 'UTF-8') ?>">Register</a>
                    </p>
                </form>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../Templates/footer.php'; ?>
<script defer src='../Assect/js/firebase-auth-pages.js'></script>
</body>

</html>


