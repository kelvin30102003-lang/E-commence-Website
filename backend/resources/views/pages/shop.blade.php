@php($activePage = 'shop')

<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
        .inner-glow {
            box-shadow: inset 0 2px 4px 0 rgba(255, 255, 255, 0.4);
        }
    </style>
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
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden">
@include('partials.header', ['activePage' => $activePage])
<main class="pt-24 pb-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto">
<!-- Search and Filter Section -->
<section class="mb-xl flex flex-col md:flex-row md:items-center justify-between gap-md">
<div class="relative w-full md:max-w-md group">
<input class="w-full h-14 pl-12 pr-6 rounded-full bg-surface-container-low border-none focus:ring-2 focus:ring-primary transition-all duration-300 text-body-md placeholder-outline shadow-sm group-hover:shadow-md" placeholder="Search for cuteness..." type="text"/>
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
</div>
<div class="flex items-center gap-sm overflow-x-auto pb-2 scrollbar-hide no-scrollbar">
<button class="px-6 py-2 rounded-full bg-primary text-on-primary text-label-md font-label-md whitespace-nowrap active:scale-95 transition-transform">All</button>
<button class="px-6 py-2 rounded-full bg-surface-container-high text-on-surface-variant text-label-md font-label-md whitespace-nowrap hover:bg-surface-variant transition-colors active:scale-95">Plushies</button>
<button class="px-6 py-2 rounded-full bg-surface-container-high text-on-surface-variant text-label-md font-label-md whitespace-nowrap hover:bg-surface-variant transition-colors active:scale-95">Stationery</button>
<button class="px-6 py-2 rounded-full bg-surface-container-high text-on-surface-variant text-label-md font-label-md whitespace-nowrap hover:bg-surface-variant transition-colors active:scale-95">Home Decor</button>
<button class="p-2 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center">
<span class="material-symbols-outlined" data-icon="tune">tune</span>
</button>
</div>
</section>
<!-- Product Grid -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-xl">
<!-- Card 1 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Cloudy Bunny Plushie" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A macro studio shot of an incredibly soft, white bunny plushie with floppy ears and a pastel pink bow. The lighting is diffused and high-key, creating a warm, dreamy atmosphere. The background is a soft peach gradient, aligning with the cute and approachable brand aesthetic of LuvShop. The plushie sits on a soft white fur texture." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCVorp-79nZBxxkPKo1rhjf2EU0YQSdMgKgfdsEfvFVgM1GPOnX6A6W1m6WDMKOHFN9FKuLw282My66TwxXliPCw6ZoxkhCr_BgjdomjWqI63gG67HCC6dSYRncTmL9sFISNZ49Kxgj4yviuY-V2XKf5IAeQmbOuj2QZ4DZ2cf2cnjDDhW8bbruTLU6SE0LxUqRUBINBI7OO0j4WpF8mXzltTw5BT6DsL-94xhru-p5AVhwXSiOqtIeyPdbGJUTS-A_kSSheUv17b0"/>
<span class="absolute top-4 left-4 bg-tertiary-container text-on-tertiary-container text-label-sm px-3 py-1 rounded-full font-label-sm">New In</span>
<button class="absolute top-4 right-4 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm">
<span class="material-symbols-outlined" data-icon="favorite">favorite</span>
</button>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Cloudy Bunny Plushie</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">The softest companion for your bedtime adventures.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$24.00</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 2 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Pastel Dreams Set" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A curated flat lay of aesthetic stationery items including pastel pink journals, mint green washi tape, and cream-colored pens. The arrangement is artistic and clean on a light marble surface. Soft sunlight shadows dance across the items, creating a cozy and inviting home office mood in a light mode palette." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDXtFV_9ENVodubkfImOUz_58Qr1rIlHpdosi33xlsLO8lcwm8cUjiDyiYG34UKg3X9878fQiBbdxLi3chDJPcDsgSWDNWvqyoodGiuwIJ-Wv7hwOSeMj0v2gkN_3I3e1pEldpmPp_u_aDT1KqjmRJ3ewSpOvaO_dt6NU96p_Tg2aP6VUsqV8dEd-G3C3yLeZ27W_HhiABeeEPyYPRmR3Abz_OPVW_AKQN3UjFRYJql334uxr5SVdxzTYx9e_7P-50Zu5H4Gx2CIMk"/>
<span class="absolute top-4 left-4 bg-tertiary-container text-on-tertiary-container text-label-sm px-3 py-1 rounded-full font-label-sm">Limited</span>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Pastel Dreams Set</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">A bundle of joy for organized and cute planning.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$38.50</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 3 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Mellow Yellow Headphones" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="Modern over-ear headphones in a matte lemon yellow finish resting on a minimalist white table next to a tiny potted succulent. The lighting is soft and natural, emphasizing the rounded terminals and tactile texture of the device. The color scheme is bright and cheerful, utilizing the LuvShop tertiary yellow." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBtXf0YnkDU6D2Q6C-GK6oRHwUwZueP-gqCqWe4GyBtAE6CSyQ8JvbuU6An1BoStwhD1w_5EtMHDgfUnD-k4TgzHV0pPgH7U-JqTm03U26eE0UCYKkp0lVwzwpC15vjZG7xvY4q68pkTHxPU-MmBin6GTxtPDC2JFTPMTlAJ7xUtqcJGm3jJ95KM_o9darOgMlLSZhvMuBIZbBRouCyif8J2zfNZGMu6E-TjPJhL8CbFANMcBHy0ZATb3UsNGffI2f_LnpdGUzFm2c"/>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Mellow Yellow Headphones</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">High-fidelity sound wrapped in a sunny aesthetic.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$89.00</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 4 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Starry Night Nightlight" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A cute star-shaped nightlight emitting a warm, soft yellow glow in a dim, cozy bedroom setting. The star has a friendly smiling face. The image style is soft-focus with bokeh lighting in the background, creating a sense of safety and domestic warmth. Palette consists of creams, warm yellows, and soft grays." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBWJgj9GlMaBjeQXtnXxVyHUQE_pjCfV34H4-izyzdUojRbOHbXSvLymHCjSjGNwKS0aJhoppZTtNQcAREfNddDMKR3jJ18rLgUJu-qjzUwmMTtQ0qhtjYrbjO4fR-mXsiV9Jr_lbhNQTh8H98WlHMhpKgs4kMjsNBL7uWRGxqrWE3KGoLkXnzmPYNd0Ccka1ZJ9lQ-hfgEv5qdOoXEADxTvvBzvxXCjHShslz2JK_JTzCc3VQCO-74skVNCvPwB9O6MjyV_BBjTK8"/>
<span class="absolute top-4 left-4 bg-error-container text-on-error-container text-label-sm px-3 py-1 rounded-full font-label-sm">Sale</span>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Starry Nightlight</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">Let the gentle glow guide you to sweet dreams.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$15.99</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Repeat some items for the grid feel -->
<!-- Card 5 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Cactus Desk Friend" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A tiny, happy cactus in a blush pink ceramic pot with a hand-painted smiling face. The scene is bright with clean white space, emphasizing the vibrant green of the succulent and the soft pink of the pot. Minimalist desk accessories are blurred in the background. High-key lighting for a clean, approachable vibe." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDyhOUb_bMZRt8goq5JdQ-HYh2__VaGqp-dKuosVaA_lD_WwNRfz1ew6w9i0HvHLpY-ci7HNULRDZv6i4ogEZCK5mACPTjxWunK2OKIanepCfCW-9qFwQnKwtPqlDWbqMqTNNHwHNdFwdTTPgOKT9IkYRiqkV7qKuk0mi0c6D0l2PgPiB3En9sIPSLoElDPAnZevG6_u1f0BeLHww-db--C4dIko90Sh31lP9wxI3nIFkZgr1p0Z5T8f2beNkZBKh3vwqkge7-JF6U"/>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Cactus Desk Friend</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">The only cactus that gives virtual hugs every day.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$12.50</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 6 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Cloudy Mug" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A white ceramic mug with a chunky, cloud-shaped handle resting on a wooden coaster. The mug contains steaming cocoa with marshmallows. The lighting is cozy and warm, coming from the side to highlight the unique, squishy-looking texture of the handle. Soft-tactile design principles in every detail." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAzPGwY_u4v7rVO10QoCgAzF_OVz73HJQheYnzdpuiDCZuoDDYJe8Xc2DSSVeCNWwgBHNK_IpvmuCiRU9BIHZoI-2B9p4DNJLoc5dyrPp2_-09fP7wbNa5N6jPI8NqgQ9jOcS1VfkhJEcD6Y9uHv4iFJAiptqCTKANjrV4VGLWNWFQ1onPk9ZQcIGwheDxRldfqsmJpch1FqzsLiUIWuasdFRyQQU-FNB6CX3a8e1bS8IC3710OyREBpupn1NleLwASRoL1YO4pUNI"/>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Marshmallow Mug</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">Chunky handles for the ultimate cozy grip experience.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$18.00</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 7 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Peach Hoodie" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A soft, oversized hoodie in a gentle peach color hanging on a minimalist wooden hanger against a clean white wall. The fabric looks incredibly plush and high-quality. Soft morning light creates a peaceful, fresh atmosphere. The palette is dominated by blush tones and light mode aesthetics." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAEKG-TjLOe21OK4W5wMTH2Crd09w-GvKsvqkbg0XeVkKpmbhmhpnSmGAyKxdZ0yC-IEdGVELDVf7_7znnMWwzaND8ZzHaZbk6D2TpsZk7iO-f770n413t3WpMhDxN5Qoi22GNv9BwLCM_3GNaipsSXC4ROVzGBJta10cFSKP-2uyWI6Ac0KSk2UO4GMSAlmCmIFPN965jFF2qa_WdXkwYhM0SsgjOHoxKVnr_j0REodMeDW2kCS3jT4kq2qUeAR8q2y7mMdjuRXNE"/>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Peach Comfort Hoodie</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">Wearable warmth for chilly evenings at home.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$45.00</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
<!-- Card 8 -->
<div class="group bg-surface-container-lowest rounded-lg p-md soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col">
<div class="relative aspect-square rounded-lg overflow-hidden mb-md bg-surface-container-low">
<img alt="Berry Scented Candle" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" data-alt="A small glass candle jar with a hand-drawn berry illustration label. The wax is a soft violet color. The candle is unlit, placed on a cozy knitted throw blanket. The overall mood is serene, domestic, and fragrant. High-key lighting with soft shadows." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBENz2me0lZMI15tbR3pEBtOv_zsadrwXjzv8p8OkuWs1rhckPHjKBKYP6cd2vUiOAIm9P5Wbwf-jrFZdUgtdSChKZHyy2wvSkyp5L0cP7y38gPYlWzFHFYVVZw7vvR5_950vlsQcsa__wHsMBam972vRS668xq7EzNqhrM2vCNp1IoTLJCgEIwLck2IA40_XhM-gl3n0-9evtuXGAvrBIt8I1E019GvDxYssgP5Gh21-FxHRGEBkIdNNKEsDQM0oE3NeqnyDo6ozE"/>
<span class="absolute top-4 left-4 bg-secondary-container text-on-secondary-container text-label-sm px-3 py-1 rounded-full font-label-sm">Bestseller</span>
</div>
<div class="flex-grow">
<h3 class="text-headline-md font-headline-md text-primary mb-xs">Berry Glow Candle</h3>
<p class="text-body-md text-on-surface-variant mb-md leading-relaxed">Natural soy wax with a hint of wild mountain berries.</p>
<div class="flex items-center justify-between mt-auto">
<span class="text-headline-md font-bold text-on-surface">$14.00</span>
<button class="bg-primary text-on-primary h-12 px-6 rounded-full font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all inner-glow">
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</div>
</div>
</div>
</section>
<!-- Pagination -->
<nav class="mt-xl flex items-center justify-center gap-sm">
<button class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="chevron_left">chevron_left</span>
</button>
<button class="w-10 h-10 rounded-full flex items-center justify-center bg-primary text-on-primary font-bold shadow-sm">1</button>
<button class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">2</button>
<button class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">3</button>
<span class="text-on-surface-variant px-2">...</span>
<button class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">12</button>
<button class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
</button>
</nav>
</main>
<!-- NavigationDrawer (Mobile Sidebar) -->
<div class="hidden fixed inset-0 z-[60] flex pointer-events-none" id="drawer">
<div class="bg-surface h-full w-80 rounded-r-lg shadow-2xl flex flex-col p-md pointer-events-auto transition-transform -translate-x-full duration-500">
<div class="flex items-center gap-md mb-xl p-md border-b border-outline-variant/30">
<div class="w-12 h-12 rounded-full bg-primary-container overflow-hidden">
<img alt="User Profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAo7eTO1_fMWlHW0ZTmn4bk0a613MBTAycLnPG-nkuduOQSFYucHj-v-IRmDEA9C04bMRhgCqjAwisrpOjYW__9DBvkcmdeYtVbZVXWJw6gEyVTsWBOikoEfiFig1WhxvzDIlv2XhcY1dX4RtOLq_VwOT8W_o8LUYOrsS-SS4zabfm-pIfcz8zf39cYaG-STjXamuaE-zrg7NePRFwciWqX1tD9AZKjXjGw8ruQqbtUCwNel1h5XrT6dNdgEzETItu3JvaB8yiAaYA"/>
</div>
<div>
<h4 class="text-body-md font-bold text-on-surface">Welcome, Friend!</h4>
<p class="text-label-sm text-on-surface-variant">Start your cute journey</p>
</div>
</div>
<nav class="flex flex-col gap-sm">
<a class="flex items-center gap-md p-md bg-secondary-container text-on-secondary-container font-bold rounded-xl" href="#">
<span class="material-symbols-outlined" data-icon="auto_awesome">auto_awesome</span>
                    New Arrivals
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="stars">stars</span>
                    Best Sellers
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="category">category</span>
                    Categories
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="sell">sell</span>
                    Sale
                </a>
</nav>
<div class="mt-auto p-md text-center">
<span class="text-label-sm text-outline-variant">Member since 2024</span>
</div>
</div>
<div class="flex-grow bg-black/20 backdrop-blur-sm pointer-events-auto"></div>
</div>
@include('partials.footer')
<!-- BottomNavBar (Mobile Only) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 pb-safe py-3 bg-surface-container-low rounded-t-lg shadow-[0_-4px_12px_rgba(120,85,94,0.08)] border-t border-outline-variant/30">
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" href="{{ route('home') }}">
<span class="material-symbols-outlined" data-icon="home">home</span>
<span class="text-label-sm font-label-sm">Home</span>
</a>
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1.5 transition-all duration-300" href="{{ route('shop') }}">
<span class="material-symbols-outlined" data-icon="storefront">storefront</span>
<span class="text-label-sm font-label-sm">Shop</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="chat_bubble">chat_bubble</span>
<span class="text-label-sm font-label-sm">Contact</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="person">person</span>
<span class="text-label-sm font-label-sm">Profile</span>
</a>
</nav>
</body></html>

