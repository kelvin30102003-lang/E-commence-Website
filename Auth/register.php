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
    <title>Create Account | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
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

        .spring-hover:hover {
            transform: scale(1.02);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .spring-active:active {
            transform: scale(0.95);
        }

        .soft-shadow {
            box-shadow: 0 20px 40px -10px rgba(120, 85, 94, 0.08);
        }

        .inner-glow:focus-within {
            box-shadow: inset 0 0 0 2px #78555e, 0 0 12px rgba(120, 85, 94, 0.1);
        }
    </style>
</head>

<body class="bg-background text-on-surface font-body-md antialiased min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/../Templates/header.php'; ?>
<!-- Main Content: Centered Registration Card -->
    <main class="flex-grow flex items-center justify-center pt-24 pb-xl px-gutter relative overflow-hidden" id="app-main">
        <!-- Background Decorations -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-container/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-tertiary-container/30 rounded-full blur-3xl"></div>
        <div class="w-full max-w-[520px] bg-surface-container-lowest rounded-xl p-xl soft-shadow z-10 border border-outline-variant/10">
            <!-- Header Section -->
            <div class="text-center mb-xl">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-container rounded-full mb-md">
                    <span class="material-symbols-outlined text-primary text-3xl" data-icon="person_add" style="font-variation-settings: 'FILL' 1;">person_add</span>
                </div>
                <h2 class="text-headline-lg font-headline-lg text-on-surface mb-xs">Create Account</h2>
                <p class="text-body-md text-on-surface-variant">Welcome to the family! Let's get you started.</p>
            </div>
            <!-- Form -->
            <form id="firebase-register-form" class="space-y-lg">
                <div class="space-y-xs">
                    <label class="text-label-md font-label-md text-on-surface-variant ml-2">Username</label>
                    <div class="relative inner-glow rounded-full">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline" data-icon="account_circle">account_circle</span>
                        <input id="firebase-register-name" class="w-full bg-surface-container-low border-none rounded-full py-4 pl-12 pr-4 text-body-md focus:ring-0 placeholder:text-outline/50" placeholder="your_name" type="text" required />
                    </div>
                </div>
                <div class="space-y-xs">
                    <label class="text-label-md font-label-md text-on-surface-variant ml-2">Email Address</label>
                    <div class="relative inner-glow rounded-full">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline" data-icon="mail">mail</span>
                        <input id="firebase-register-email" class="w-full bg-surface-container-low border-none rounded-full py-4 pl-12 pr-4 text-body-md focus:ring-0 placeholder:text-outline/50" placeholder="hello@luvshop.com" type="email" required />
                    </div>
                </div>
                <div class="space-y-xs">
                    <label class="text-label-md font-label-md text-on-surface-variant ml-2">Password</label>
                    <div class="relative inner-glow rounded-full">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline" data-icon="lock">lock</span>
                        <input id="firebase-register-password" class="w-full bg-surface-container-low border-none rounded-full py-4 pl-12 pr-12 text-body-md focus:ring-0 placeholder:text-outline/50" placeholder="********" type="password" minlength="6" required />
                        <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline" type="button">
                            <span class="material-symbols-outlined text-sm" data-icon="visibility">visibility</span>
                        </button>
                    </div>
                </div>
                <p id="firebase-register-feedback" class="min-h-[24px] text-body-md"></p>
                <button class="w-full bg-primary text-on-primary font-headline-md text-lg py-4 rounded-full shadow-lg spring-hover spring-active transition-all mt-md" type="submit">
                    Join the Club
                </button>
            </form>
            <!-- Divider -->
            <div class="flex items-center my-xl gap-md">
                <div class="flex-grow h-px bg-outline-variant/30"></div>
                <span class="text-label-sm font-label-sm text-outline uppercase tracking-widest">Or sign up with</span>
                <div class="flex-grow h-px bg-outline-variant/30"></div>
            </div>
            <!-- Social Logins -->
            <div class="flex gap-md mb-xl">
                <button class="spring-btn flex-1 h-14 border-2 border-outline-variant/30 rounded-full flex items-center justify-center gap-sm hover:bg-surface-container transition-colors" data-google-auth="register" type="button">
                    <img alt="Google" class="w-5 h-5" src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" />
                    <span class="text-label-md font-label-md text-on-surface">Google</span>
                </button>
                
            </div>
            <!-- Redirect Link -->
            <div class="text-center">
                <p class="text-body-md text-on-surface-variant">
                    Already have an account?
                    <a class="text-primary font-bold hover:underline decoration-2 underline-offset-4" data-ajax="true" href="login.php<?= htmlspecialchars($authRedirectQuery, ENT_QUOTES, 'UTF-8') ?>">Log In</a>
                </p>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../Templates/footer.php'; ?>
<script defer src='../Assect/js/firebase-auth-pages.js'></script>
</body>

</html>

