<?php
$activePage = 'home';
?>

<!DOCTYPE html>

<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&amp;family=Plus+Jakarta+Sans:wght@200..800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
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

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 300px);
            gap: 24px;
        }
    </style>
</head>

<body class="bg-background text-on-surface font-body-md selection:bg-primary-container selection:text-on-primary-container">
    <?php require_once __DIR__ . '/../Templates/header.php'; ?>
    <main class="pt-16" id="app-main">
        <!-- Hero Banner -->
        <section class="relative w-full h-[819px] flex items-center overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img class="w-full h-full object-cover" data-alt="A dreamy and soft-focus lifestyle scene featuring cute aesthetic home decor items like plush cushions, pastel ceramics, and warm fairy lights. The lighting is golden hour soft, creating a cozy and inviting domestic atmosphere that feels safe and delightful. The color palette is dominated by blush pinks, soft creams, and hints of muted gold, perfectly aligning with the cute and approachable brand identity of LuvShop." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCBVb4xlZ4RjfEGIXijrKdQorRNhqafxAjmzyGvTawYOH2Q02pyrOBvkjZfyAjXXCHasvrJNvrzyWbPuYrrcFbbVlZxn4ZhLKOUyBJ5ACPZk9ZotYSnAoM_B27JYOqNxcoXZ_xAJO6G81MlMXew6yYj_EhKP2NCk1W-ZTUj2qzenFNfMjMGU62TUTjWJrfTO6EHWbob5tZFz2t0ci8ZTG5LCLw7j-J7q_c-kEIZaXbORHlu9N_Y_xWxc51__81W_UFbh4ePD-_uNTQ" />
                <div class="absolute inset-0 bg-gradient-to-r from-background/90 via-background/40 to-transparent"></div>
            </div>
            <div class="relative z-10 max-w-[1280px] mx-auto px-margin-desktop w-full">
                <div class="max-w-xl space-y-lg">
                    <span class="inline-block px-md py-xs bg-tertiary-fixed text-on-tertiary-fixed rounded-full text-label-md font-label-md">New Collection ✨</span>
                    <h1 class="text-display-lg font-display-lg text-primary leading-tight">Welcome to LuvShop</h1>
                    <p class="text-body-lg font-body-lg text-on-surface-variant">Discover a curated world of cute essentials designed to bring a little extra sparkle and warmth to your everyday life.</p>
                    <div class="flex gap-md pt-md">
                        <button class="px-xl py-md bg-primary text-on-primary rounded-full font-label-md text-label-md hover:opacity-90 shadow-lg active:scale-95 transition-all">Shop Now</button>
                        <button class="px-xl py-md bg-secondary-container text-on-secondary-container rounded-full font-label-md text-label-md hover:bg-secondary-fixed active:scale-95 transition-all">Learn More</button>
                    </div>
                </div>
            </div>
        </section>
        <!-- Featured Products Bento Grid -->
        <section class="max-w-[1280px] mx-auto px-margin-desktop py-xl">
            <div class="flex items-end justify-between mb-xl">
                <div class="space-y-xs">
                    <h2 class="text-headline-lg font-headline-lg text-primary">Featured Treasures</h2>
                    <p class="text-on-surface-variant font-body-md">Hand-picked delights just for you</p>
                </div>
                <button class="flex items-center gap-sm text-primary font-label-md">
                    View All <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
            <div class="grid grid-cols-12 grid-rows-2 gap-lg h-[800px]">
                <!-- Large Featured Card -->
                <div class="col-span-6 row-span-2 relative group overflow-hidden rounded-lg bg-surface-container-low shadow-sm">
                    <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" data-alt="A close-up shot of a high-quality, handcrafted ceramic mug with a cute whimsical character design. The scene is bright and airy with soft morning sunlight hitting a wooden table. Pastel colors like blush pink and lemon yellow are prominent, creating a cozy and cheerful morning ritual vibe." src="https://lh3.googleusercontent.com/aida-public/AB6AXuA7nx3bnappTLABUiFnEGzPQMyqRD3-CLkua0a8wcxMIyqhUg1dAut-P5zt2lC6JxWkp59pr4LKbfyblRMXeCA23EBDkJkX5vUwCKP1cdliCroezGP-dn7pG-ZKzuqXMfKbGLAaC8f4Dr5BTscwez7MvYRSCbVYJ7aQp7bF6jTscubq_Ya4c93H86qgNZewbmK9Ebzo3_G2wTbzMp7oobV7Z-X66tEG903jrnIY4U2OUce0GUebsNutBWEL7O83TXLf9kl9PP5Z1oc" />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/60 to-transparent flex flex-col justify-end p-xl text-white">
                        <span class="text-label-md font-label-md mb-xs opacity-90">Daily Essential</span>
                        <h3 class="text-headline-md font-headline-md mb-md">Whimsical Morning Mugs</h3>
                        <button class="w-fit px-lg py-sm bg-white text-primary rounded-full font-label-md">Explore Series</button>
                    </div>
                </div>
                <!-- Secondary Cards -->
                <div class="col-span-6 row-span-1 relative group overflow-hidden rounded-lg bg-tertiary-container shadow-sm">
                    <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" data-alt="A collection of soft, pastel-colored stationary and planners laid out on a clean white desk. The lighting is high-key and shadowless, emphasizing the 'clean and organized' aesthetic. Tactile textures of high-quality paper and fabric covers are visible, evoking a sense of productivity and calm." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAgJjW_4riUH1p6rPWBRf22rMQ9TA2P2qKBOhPrc55f_mXZmVRmeFB2vjjO0hGdeBoTGr7vMQxlFKU96DllcSMygEvYHvJHuCjFy3Q7mYCRAl0txGtbvb4i64Vqa57mZhky3rKGje4HgBDmfDg08VJt22wkwwkysZ4sGbKkVQ5m01B2iCIyrU28r9WsWuBdTRhluFypDbMH3vqJVlxEpyGUeuXPIGATIoBoF0Q1X6VwQcEdyGkuVE7iSAFK-JNvdLhsa1y_TF2YuH0" />
                    <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                        <div class="bg-white/90 backdrop-blur-md px-lg py-md rounded-xl text-center transform translate-y-4 group-hover:translate-y-0 transition-transform">
                            <p class="font-headline-md text-primary">Stationery Sets</p>
                            <p class="text-label-sm font-label-sm text-on-surface-variant">From $24.00</p>
                        </div>
                    </div>
                </div>
                <div class="col-span-3 row-span-1 relative group overflow-hidden rounded-lg bg-secondary-container shadow-sm">
                    <img class="w-full h-full object-cover" data-alt="A soft plush toy shaped like a round, friendly cloud with embroidered eyes and a pink blush. It sits against a background of soft textures like wool and cotton. The lighting is diffused and warm, emphasizing the 'squishy' and safe tactile nature of the product." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAW8fUMEDPBmNZSQIeH0TzWDGcxBtxf_Vy4ao2cTg4PHwbU4HLb1BuXOtFbDfskP2afuqRWyooZE-sLNULfFQX7Au6eWflRf2Z6YrDfYFRmt307CRwRU86dYM1gj1ttzL4q9tB6y2ENIl6ftNgq6eFG2V9NrTtS_MgjXIqn_Wh8pGj--mPcgPDHDLieAQsKnoHcgS7Y3dtuccKt4g7Z0pngzNAwnRALnaNx13sK-T1drB2LE28iUtSbViR54iCIaVNjFUXCh9BFkZc" />
                    <div class="absolute bottom-4 left-4 right-4 bg-white/80 p-md rounded-lg">
                        <p class="font-label-md text-primary">Cloud Plush</p>
                    </div>
                </div>
                <div class="col-span-3 row-span-1 relative group overflow-hidden rounded-lg bg-primary-container shadow-sm">
                    <img class="w-full h-full object-cover" data-alt="A beautifully packaged gift box tied with a satin ribbon in blush pink. The box is surrounded by dried flower petals and delicate tissue paper. The visual style is premium yet domestic, showcasing the care and love that goes into LuvShop packaging." src="https://lh3.googleusercontent.com/aida-public/AB6AXuA_NLIZxwq9heFiNad9wXWmU2I3dqXxGTj9dz-GvsGIzsKN-DbF_npcSy_exuQcV7ZiR02Xc5FjFpIixcFPAsosJswMot2ajdHBYjSPWXy-vSXyyWYtXWAl2dNAYrKSQPuDExKRwQAcEYK0pFjJvczJibzOMMIy-pmFhnkm2OngWdlUR32aUkEgzXnJ7g6kU8vBBFuOV8Z_H6A7fGRxacGisNvOtpr8HShv8zOIT4zGm22TFxmp5btndm46pTRv1TGqcmx_M7sDdYc" />
                    <div class="absolute bottom-4 left-4 right-4 bg-white/80 p-md rounded-lg">
                        <p class="font-label-md text-primary">Gift Boxes</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- New Arrivals Spotlight -->
        <section class="bg-surface-container-low py-xl overflow-hidden">
            <div class="max-w-[1280px] mx-auto px-margin-desktop">
                <div class="bg-white rounded-lg p-xl flex flex-col md:flex-row items-center gap-xl shadow-sm">
                    <div class="w-full md:w-1/2 space-y-md">
                        <div class="flex items-center gap-sm text-secondary font-label-md">
                            <span class="material-symbols-outlined" data-icon="stars" data-weight="fill" style="font-variation-settings: 'FILL' 1;">stars</span>
                            Spotlight Collection
                        </div>
                        <h2 class="text-headline-lg font-headline-lg text-primary">The "Cuddle-Up" Winter Series</h2>
                        <p class="text-body-lg font-body-lg text-on-surface-variant">Our latest arrivals have landed! Featuring ultra-soft fabrics, calming pastel tones, and designs that feel like a warm hug. Perfect for those cozy nights in or brightening up a chilly afternoon.</p>
                        <div class="flex flex-wrap gap-sm">
                            <span class="px-md py-xs bg-tertiary-container text-on-tertiary-container rounded-full text-label-sm font-label-sm">Limited Edition</span>
                            <span class="px-md py-xs bg-secondary-container text-on-secondary-container rounded-full text-label-sm font-label-sm">Best Seller Potential</span>
                        </div>
                        <button class="mt-lg px-xl py-md border-2 border-primary text-primary rounded-full font-label-md hover:bg-primary hover:text-on-primary transition-colors active:scale-95">Explore Collection</button>
                    </div>
                    <div class="w-full md:w-1/2 grid grid-cols-2 gap-md">
                        <img class="rounded-lg h-64 w-full object-cover" data-alt="A close up of a soft wool knit blanket in a muted lavender color. The texture is intricate and chunky, emphasizing the comfort and warmth of the new winter collection. The lighting is low and moody but still bright enough to see the detailed fibers of the yarn." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBWYhNtTt1Zw2lPgaZx6Rhz9pjL0yBxH9bWInurmtqAjFZft3IxKoq0sdoi_6JXw4cOggKtsZ2YH0db-Ihqx2O2-j4HTtZyvkNSQHQa1BZ52G5XPUr56PpumYkoPUxXOyLX8aReZ98LKe6zTcmyqO-MI9QAN1rOry10BjvTklApWhKRRPLthX4cCU-8UXnmQ2txKI4tuDLIphHZyLv4PKhLFXmcRS2pn87oGlrpNaq92OBZCsX7HpxiRyPJdfLDma1jKjjx4GEUBxY" />
                        <img class="rounded-lg h-64 w-full object-cover mt-lg" data-alt="An assortment of scented candles in aesthetic matte glass jars. The jars are colored in soft sage green, blush pink, and pale lemon. The candles are unlit, sitting on a marble tray surrounded by soft cotton stems. The atmosphere is serene and relaxing." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAyZrMmL1y-eXpZ-jrdQmsxggkt45NnsrCdgFvsMjxX-tvVZYZ9eTYx2iWjq_hw7l4eOKWgNlN-0FwUej99gdOQx7IQNsaBqtuApCJGApuqghPZWawjRcopb9QaUhL4AdW6iQZs82xnoQcwATq2At-1__O3O9raNN_ULfOLOOM_4cFJpNa_1F7AJdCiSHa71pfq-9Y6bHkT8VGnTo7qgL75db-cQ9_OhIzY8mYraq4PxDn-X1EIoPO7O1jLCGpHPv8TmZM20yCPreM" />
                    </div>
                </div>
            </div>
        </section>
        <!-- Newsletter Signup: LuvClub -->
        <section class="max-w-[1280px] mx-auto px-margin-desktop py-xl">
            <div class="relative bg-primary-container/30 rounded-lg p-xl text-center overflow-hidden">
                <!-- Decorative Shapes -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-tertiary-fixed-dim/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 -right-12 w-64 h-64 bg-secondary-fixed-dim/20 rounded-full blur-3xl"></div>
                <div class="relative z-10 max-w-2xl mx-auto space-y-md">
                    <span class="material-symbols-outlined text-primary text-5xl" data-icon="favorite">favorite</span>
                    <h2 class="text-headline-lg font-headline-lg text-primary">Join the LuvClub</h2>
                    <p class="text-body-md font-body-md text-on-surface-variant">Sign up for our newsletter to receive exclusive offers, early access to new drops, and a weekly dose of cute directly to your inbox.</p>
                    <form class="flex flex-col sm:flex-row gap-md mt-lg max-w-lg mx-auto">
                        <input class="flex-grow px-lg py-md rounded-full border-none bg-white shadow-inner focus:ring-2 focus:ring-primary text-body-md" placeholder="Your sweet email here..." required="" type="email" />
                        <button class="px-xl py-md bg-primary text-on-primary rounded-full font-label-md shadow-md hover:opacity-90 active:scale-95 transition-all" type="submit">Join Now</button>
                    </form>
                    <p class="text-label-sm font-label-sm text-on-surface-variant/60">We promise to only send things that make you smile. Unsubscribe anytime.</p>
                </div>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../Templates/footer.php'; ?>
    <!-- FAB (Suppressed on Home by logic but often useful for specific triggers) -->
    <button class="fixed bottom-margin-desktop right-margin-desktop w-14 h-14 bg-primary text-on-primary rounded-full shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 transition-transform z-40">
        <span class="material-symbols-outlined" data-icon="chat">chat</span>
    </button>
</body>

</html>

