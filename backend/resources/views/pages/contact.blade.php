@php($activePage = 'contact')

<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Contact Us - LuvShop</title>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Google Fonts: Quicksand & Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&amp;family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                      "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                      "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                      "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                      "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                      "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                      "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                      "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "700"}],
                      "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}]
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
        .soft-pill-inner-glow {
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.4);
        }
        .spring-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .spring-active:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="text-on-background">
@include('partials.header', ['activePage' => $activePage])
<!-- Main Content Canvas -->
<main class="pt-24 pb-xl px-margin-mobile md:px-margin-desktop max-w-[1280px] mx-auto min-h-screen">
<!-- Header Section -->
<div class="mb-xl text-center md:text-left">
<h2 class="text-display-lg font-headline-lg text-primary mb-sm">Let's be friends!</h2>
<p class="text-body-lg font-body-lg text-on-surface-variant max-w-2xl">Have a question or just want to share some love? We're here to help you make your shopping journey extra special.</p>
</div>
<!-- Split Layout Bento Style -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-lg">
<!-- Left Side: Illustration & Details -->
<div class="md:col-span-5 flex flex-col gap-lg">
<!-- Cute Illustration Card -->
<div class="bg-primary-container/30 rounded-lg p-xl flex items-center justify-center relative overflow-hidden group">
<div class="absolute inset-0 bg-gradient-to-tr from-primary-container/20 to-transparent opacity-50"></div>
<img alt="Cute bear with a letter" class="w-full h-auto max-w-[320px] relative z-10 drop-shadow-xl transform group-hover:rotate-3 transition-transform duration-500" data-alt="A whimsical and soft 3D illustration of a chubby, friendly teddy bear wearing a miniature mailman cap. The bear is happily holding a pastel pink envelope with a tiny heart seal. The background is a soft, dreamlike studio setting with warm peach lighting and floating sparkles, maintaining a cute, domestic, and approachable aesthetic consistent with the LuvShop brand." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBOZOm9frR0VW3ZIAh1wer2le5aAC3pbEiqzfgeGEnemgZEcN5NjUIYBO1Mt2uNCq3ag-Usad99AR3mqe3TSddlCzMtsLxBAuXYcTRYEDKEPgmpzN9C05th988J-9-83PNFUfWh7_0a5muIhcw2eB_-jxrczy2azppZ9yzaNXkTu2NJdaAP7MP-TOCsbg85w913IC09Jnaf3NSbZGfVrbCh98vHvSX7HfzucvlqX4XXRStLLsnzC8iBX_fvsMgQlcoeNhjMBxISljc"/>
</div>
<!-- Contact Details Card -->
<div class="bg-surface-container rounded-lg p-lg shadow-sm border border-outline-variant/20">
<h3 class="text-headline-md font-headline-md text-primary mb-md">Reach Out</h3>
<div class="space-y-md">
<div class="flex items-center gap-md p-md bg-surface-container-low rounded-lg transition-colors hover:bg-surface-container-high">
<div class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed-variant">
<span class="material-symbols-outlined" data-icon="mail">mail</span>
</div>
<div>
<p class="text-label-sm font-label-sm text-outline uppercase tracking-wider">Email Us</p>
<p class="text-body-md font-body-md font-bold">hello@luvshop.com</p>
</div>
</div>
<div class="flex items-center gap-md p-md bg-surface-container-low rounded-lg transition-colors hover:bg-surface-container-high">
<div class="w-12 h-12 rounded-full bg-tertiary-fixed flex items-center justify-center text-on-tertiary-fixed-variant">
<span class="material-symbols-outlined" data-icon="call">call</span>
</div>
<div>
<p class="text-label-sm font-label-sm text-outline uppercase tracking-wider">Call Us</p>
<p class="text-body-md font-body-md font-bold">+1 (555) 123-4567</p>
</div>
</div>
<div class="flex items-center gap-md p-md bg-surface-container-low rounded-lg transition-colors hover:bg-surface-container-high">
<div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant">
<span class="material-symbols-outlined" data-icon="location_on">location_on</span>
</div>
<div>
<p class="text-label-sm font-label-sm text-outline uppercase tracking-wider">Visit Us</p>
<p class="text-body-md font-body-md font-bold">123 Cute Street, Sparkle City</p>
</div>
</div>
</div>
</div>
</div>
<!-- Right Side: Contact Form -->
<div class="md:col-span-7">
<div class="bg-white rounded-lg p-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-outline-variant/10 h-full">
<div class="mb-lg">
<span class="inline-block px-4 py-1.5 rounded-full bg-tertiary-container text-on-tertiary-container text-label-sm font-label-sm mb-sm">Get in Touch</span>
<h3 class="text-headline-lg font-headline-lg text-primary">Send us a message</h3>
</div>
<form class="space-y-lg">
<div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
<div class="space-y-xs">
<label class="text-label-md font-label-md text-on-surface ml-2">Name</label>
<input class="w-full px-lg py-md rounded-full bg-surface-container-lowest border-none ring-2 ring-transparent focus:ring-primary focus:bg-white transition-all text-body-md outline-none placeholder:text-outline-variant" placeholder="Your sweet name" type="text"/>
</div>
<div class="space-y-xs">
<label class="text-label-md font-label-md text-on-surface ml-2">Email</label>
<input class="w-full px-lg py-md rounded-full bg-surface-container-lowest border-none ring-2 ring-transparent focus:ring-primary focus:bg-white transition-all text-body-md outline-none placeholder:text-outline-variant" placeholder="example@luv.com" type="email"/>
</div>
</div>
<div class="space-y-xs">
<label class="text-label-md font-label-md text-on-surface ml-2">Subject</label>
<input class="w-full px-lg py-md rounded-full bg-surface-container-lowest border-none ring-2 ring-transparent focus:ring-primary focus:bg-white transition-all text-body-md outline-none placeholder:text-outline-variant" placeholder="What's on your mind?" type="text"/>
</div>
<div class="space-y-xs">
<label class="text-label-md font-label-md text-on-surface ml-2">Message</label>
<textarea class="w-full px-lg py-md rounded-lg bg-surface-container-lowest border-none ring-2 ring-transparent focus:ring-primary focus:bg-white transition-all text-body-md outline-none placeholder:text-outline-variant resize-none" placeholder="Write your message here..." rows="5"></textarea>
</div>
<div class="pt-md">
<button class="w-full md:w-auto px-xl py-lg bg-primary text-white font-label-md text-label-md rounded-full flex items-center justify-center gap-sm shadow-lg spring-hover spring-active soft-pill-inner-glow transition-all" type="submit">
<span>Send Message</span>
<span class="material-symbols-outlined" data-icon="send">send</span>
</button>
</div>
</form>
<div class="mt-xl pt-xl border-t border-outline-variant/10 flex flex-col md:flex-row items-center justify-between gap-md">
<p class="text-body-md font-body-md text-on-surface-variant text-center md:text-left">We usually respond within 24 cute hours!</p>
<div class="flex gap-md">
<button class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-primary hover:bg-primary-container transition-colors">
<span class="material-symbols-outlined" data-icon="share">share</span>
</button>
<button class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-primary hover:bg-primary-container transition-colors">
<span class="material-symbols-outlined" data-icon="favorite">favorite</span>
</button>
</div>
</div>
</div>
</div>
</div>
</main>
@include('partials.footer')
<!-- BottomNavBar (Mobile only) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 pb-safe py-3 bg-surface-container-low border-t border-outline-variant/30 shadow-[0_-4px_12px_rgba(120,85,94,0.08)] rounded-t-lg">
<div class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="home">home</span>
<span class="text-label-sm font-label-sm">Home</span>
</div>
<div class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="storefront">storefront</span>
<span class="text-label-sm font-label-sm">Shop</span>
</div>
<div class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1.5 transition-all duration-300 ease-out scale-90">
<span class="material-symbols-outlined" data-icon="chat_bubble" style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
<span class="text-label-sm font-label-sm">Contact</span>
</div>
<div class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="login">login</span>
<span class="text-label-sm font-label-sm">Login</span>
</div>
<div class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="person">person</span>
<span class="text-label-sm font-label-sm">Profile</span>
</div>
</nav>
</body></html>
