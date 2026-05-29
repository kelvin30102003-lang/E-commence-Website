<?php

declare(strict_types=1);

$activePage = 'about';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();
?>

<!DOCTYPE html>
<html class="light scroll-smooth" lang="en">

<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Our Story | LuvShop</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&amp;family=Plus+Jakarta+Sans:wght@200..800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
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
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    spacing: {
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
                    fontFamily: {
                        "headline-md": ["Quicksand"],
                        "headline-lg": ["Quicksand"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Quicksand"],
                        "display-lg": ["Quicksand"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"]
                    },
                    fontSize: {
                        "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "700" }],
                        "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "0", "fontWeight": "700" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "fontWeight": "700" }],
                        "label-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0", "fontWeight": "600" }]
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .bubbly-hover {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        }

        .bubbly-hover:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .bubbly-hover:active {
            transform: scale(0.97);
        }

        .soft-shadow {
            box-shadow: 0 18px 45px -20px rgba(120, 85, 94, 0.28);
        }

        .about-reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 700ms ease, transform 700ms ease;
        }

        .about-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-body-md overflow-x-hidden selection:bg-primary-container selection:text-on-primary-container">
<?php require_once __DIR__ . '/../Templates/header.php'; ?>

<main id="app-main" class="pt-16">
    <section class="relative overflow-hidden px-margin-mobile py-20 md:px-margin-desktop md:py-28">
        <div class="mx-auto grid max-w-[1280px] items-center gap-12 md:grid-cols-2">
            <div class="about-reveal z-10">
                <span class="mb-6 inline-block rounded-full bg-primary-container px-4 py-1 text-label-sm font-label-sm uppercase text-on-primary-container">Welcome to our world</span>
                <h1 class="mb-6 text-display-lg font-display-lg leading-tight text-primary">Our Story</h1>
                <p class="mb-8 max-w-xl text-body-lg font-body-lg text-on-surface-variant">
                    At LuvShop, we believe everyday shopping should feel warm, thoughtful, and a little more delightful. Our journey began with a simple idea: curating pieces that bring comfort, color, and care into real homes.
                </p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a class="inline-flex justify-center rounded-full bg-primary px-8 py-4 text-label-md font-label-md text-on-primary soft-shadow bubbly-hover" data-ajax="true" href="shop.php">Explore Collection</a>
                    <a class="inline-flex justify-center rounded-full border-2 border-primary px-8 py-4 text-label-md font-label-md text-primary bubbly-hover" href="#values">Learn More</a>
                </div>
            </div>

            <div class="about-reveal relative">
                <img
                    alt="Pastel stationery, plush toys, and desk accessories arranged on a bright tabletop"
                    class="relative z-10 h-[360px] w-full rounded-xl border-8 border-white object-cover soft-shadow md:h-[500px]"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuA-xrTmIGIVze05LVdooe2bEpDD_vA9eS6GBMtYE6s1xG_U4WlVjrEPvnwdc64kSK9HJ9PQz8ZyxD-QsXCJkilTRZDw8mvN-JNlsrCFl-MqptEwPBrscYIsoVkkeft3QHY6jr5QQL8v3FEW1d9fiaJgmggq6EvSwk9q3kjubyPif0lNDySI-GFy0sBaoucd3WuCy8iNvxnJQZDWm4i5HfIbOYk9RIBORc2EWXMTTAdoN0_z3j4-0EjVdN-SFasvx78qMGRyUV8IIzE"
                />
            </div>
        </div>
    </section>

    <section id="values" class="bg-surface-container-low px-margin-mobile py-20 md:px-margin-desktop md:py-24">
        <div class="mx-auto max-w-[1280px]">
            <div class="about-reveal mb-12 text-center">
                <h2 class="mb-4 text-headline-lg font-headline-lg text-primary">Values at Our Heart</h2>
                <p class="text-body-md font-body-md text-on-surface-variant">We are on a mission to make kindness, quality, and joy part of every order.</p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <article class="about-reveal flex flex-col items-center rounded-lg border border-surface-variant/50 bg-white p-8 text-center soft-shadow bubbly-hover">
                    <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary-container">
                        <span class="material-symbols-outlined text-3xl text-on-primary-container" style="font-variation-settings: 'FILL' 1;">favorite</span>
                    </div>
                    <h3 class="mb-4 text-headline-md font-headline-md text-on-surface">Quality with a Heart</h3>
                    <p class="text-body-md font-body-md text-on-surface-variant">Every product is selected for the way it feels, functions, and fits into everyday life.</p>
                </article>

                <article class="about-reveal flex flex-col items-center rounded-lg border border-surface-variant/50 bg-white p-8 text-center soft-shadow bubbly-hover">
                    <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-secondary-container">
                        <span class="material-symbols-outlined text-3xl text-on-secondary-container" style="font-variation-settings: 'FILL' 1;">eco</span>
                    </div>
                    <h3 class="mb-4 text-headline-md font-headline-md text-on-surface">Considered Choices</h3>
                    <p class="text-body-md font-body-md text-on-surface-variant">We look for durable materials, useful design, and suppliers who care about the details.</p>
                </article>

                <article class="about-reveal flex flex-col items-center rounded-lg border border-surface-variant/50 bg-white p-8 text-center soft-shadow bubbly-hover">
                    <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-tertiary-container">
                        <span class="material-symbols-outlined text-3xl text-on-tertiary-container" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                    </div>
                    <h3 class="mb-4 text-headline-md font-headline-md text-on-surface">Spreading Joy</h3>
                    <p class="text-body-md font-body-md text-on-surface-variant">From product discovery to delivery, each step is shaped to make the experience feel personal.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="overflow-hidden px-margin-mobile py-20 md:px-margin-desktop md:py-24">
        <div class="mx-auto max-w-[1280px]">
            <div class="grid items-center gap-14 md:grid-cols-2 md:gap-20">
                <div class="about-reveal relative order-2 md:order-1">
                    <img
                        alt="Bright creative studio with mood boards, fabric swatches, and design sketches"
                        class="relative z-10 h-[420px] w-full rounded-xl border-8 border-white object-cover soft-shadow md:h-[600px]"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuB-GZe94upc3FpMsvii1tQyX75Ictknjkr5BhdK00Jq1iio5C1fGd6TOCPZYiaowbiYWc70WetnhriwR2I89teSJPscRnj5SYefoCCe0WZJtLMW5fD8AhT2J53Uh4zL-OkPYQYnlSMZ3kfUsoF1CxBxm2kVxrFs2A-Awcj01q5t_Wt1INv2OMHQMsJjkOP2f9RlGm6l3HXqkLijqpbWKctIjX_GngrxGoQjK4hemeQp4oqFWmGDqGlAGNl6DynqrRmTsKCxazD-0VE"
                    />
                    <div class="absolute -bottom-5 right-4 z-20 flex items-center gap-4 rounded-lg bg-tertiary p-5 text-on-tertiary soft-shadow md:-right-5">
                        <span class="material-symbols-outlined text-4xl">verified</span>
                        <div>
                            <div class="font-headline-md text-headline-md leading-none">Trusted</div>
                            <div class="text-label-sm font-label-sm">Curated with care</div>
                        </div>
                    </div>
                </div>

                <div class="about-reveal order-1 md:order-2">
                    <h2 class="mb-8 text-headline-lg font-headline-lg text-primary">The Journey</h2>
                    <div class="space-y-10">
                        <div class="relative border-l-2 border-primary-container pl-10">
                            <div class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-4 border-white bg-primary"></div>
                            <h3 class="mb-2 text-headline-md font-headline-md text-primary">2020: The Spark</h3>
                            <p class="text-body-md font-body-md text-on-surface-variant">A small collection of cozy finds turned into a clear point of view: useful products can still feel sweet and expressive.</p>
                        </div>
                        <div class="relative border-l-2 border-primary-container pl-10">
                            <div class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-4 border-white bg-primary"></div>
                            <h3 class="mb-2 text-headline-md font-headline-md text-primary">2022: Growing Community</h3>
                            <p class="text-body-md font-body-md text-on-surface-variant">We opened our digital doors and found customers who loved soft colors, honest details, and friendly service.</p>
                        </div>
                        <div class="relative pl-10">
                            <div class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-4 border-white bg-primary"></div>
                            <h3 class="mb-2 text-headline-md font-headline-md text-primary">Today: More Thoughtful Finds</h3>
                            <p class="text-body-md font-body-md text-on-surface-variant">LuvShop continues to grow as a curated destination for gifts, daily comforts, and cheerful essentials.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-surface px-margin-mobile py-20 md:px-margin-desktop md:py-24">
        <div class="mx-auto max-w-[1280px]">
            <div class="about-reveal mb-12 text-center">
                <h2 class="mb-4 text-headline-lg font-headline-lg text-primary">Meet the Dreamers</h2>
                <p class="text-body-md font-body-md text-on-surface-variant">The hearts and hands behind the LuvShop experience.</p>
            </div>

            <div class="mx-auto grid max-w-3xl grid-cols-1 gap-8 sm:grid-cols-2">
                <article class="about-reveal group text-center">
                    <div class="mb-5 aspect-square overflow-hidden rounded-full border-4 border-white soft-shadow transition-colors duration-300 group-hover:border-primary-container">
                        <img alt="Myint Myat Aung portrait" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-110" src="../Assect/images/team/cv.png"/>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-on-surface">Myint Myat Aung</h3>
                    <p class="text-label-md font-label-md text-primary">Founder Developer</p>
                </article>

                <article class="about-reveal group text-center">
                    <div class="mb-5 aspect-square overflow-hidden rounded-full border-4 border-white soft-shadow transition-colors duration-300 group-hover:border-secondary-container">
                        <img alt="Myat Theingi Kyaw portrait" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-110" src="../Assect/images/team/myatcv.jpg"/>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-on-surface">Myat Theingi Kyaw</h3>
                    <p class="text-label-md font-label-md text-secondary">Founder Developer</p>
                </article>

                
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../Templates/footer.php'; ?>

<script>
    (() => {
        const animatedElements = document.querySelectorAll(".about-reveal");
        if (!("IntersectionObserver" in window)) {
            animatedElements.forEach((element) => element.classList.add("is-visible"));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        animatedElements.forEach((element) => observer.observe(element));
    })();
</script>
</body>

</html>
