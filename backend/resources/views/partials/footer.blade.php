<footer class="w-full bg-tertiary-container dark:bg-tertiary px-6 py-12 md:px-10 md:py-14 text-left mt-xl">
    <div class="mx-auto max-w-[1280px]">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.45fr_1fr_1.05fr_1fr_1.25fr] lg:gap-12">
            <div class="space-y-8">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim" data-icon="bubble_chart">bubble_chart</span>
                        <span class="text-headline-md font-headline-md font-bold text-primary dark:text-primary-fixed-dim">LuvShop</span>
                    </div>
                    <p class="text-label-sm font-label-sm text-on-tertiary-container/70 dark:text-on-tertiary/70">Spreading joy, one cute item at a time.</p>
                </div>

                <form class="max-w-[280px] space-y-4" action="#" onsubmit="return false;">
                    <label class="block text-body-md font-body-md font-semibold text-on-tertiary-container dark:text-on-tertiary" for="footer-email">Subscribe Now</label>
                    <div class="flex items-center gap-3 border-b border-on-tertiary-container/45 pb-2 dark:border-on-tertiary/45">
                        <span class="material-symbols-outlined text-[18px] text-primary dark:text-primary-fixed-dim" data-icon="mail">mail</span>
                        <input class="min-w-0 flex-1 border-0 bg-transparent p-0 text-label-sm font-label-sm text-on-tertiary-container placeholder:text-on-tertiary-container/55 focus:ring-0 dark:text-on-tertiary dark:placeholder:text-on-tertiary/60" id="footer-email" name="email" placeholder="Enter your Email" type="email">
                    </div>
                    <button class="rounded-md bg-primary px-5 py-2 text-label-sm font-label-sm font-semibold text-on-primary transition-opacity hover:opacity-90 dark:bg-primary-fixed-dim dark:text-on-primary-fixed" type="button">Subscribe</button>
                </form>
            </div>

            <nav aria-label="Information" class="space-y-4">
                <h2 class="text-body-lg font-body-md font-semibold text-on-tertiary-container dark:text-on-tertiary">Information</h2>
                <div class="flex flex-col gap-3">
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('home') }}">About Us</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('shop') }}">More Search</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('home') }}">Blog</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('contact') }}">Testimonials</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('home') }}">Events</a>
                </div>
            </nav>

            <nav aria-label="Helpful links" class="space-y-4">
                <h2 class="text-body-lg font-body-md font-semibold text-on-tertiary-container dark:text-on-tertiary">Helpful Links</h2>
                <div class="flex flex-col gap-3">
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('contact') }}">Services</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('contact') }}">Supports</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="#">Terms &amp; Condition</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="#">Privacy Policy</a>
                </div>
            </nav>

            <nav aria-label="Our services" class="space-y-4">
                <h2 class="text-body-lg font-body-md font-semibold text-on-tertiary-container dark:text-on-tertiary">Our Services</h2>
                <div class="flex flex-col gap-3">
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('shop') }}">Brands list</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('shop') }}">Order</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('contact') }}">Return &amp; Exchange</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('shop') }}">Fashion list</a>
                    <a class="text-sm font-body-md text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="{{ route('home') }}">Blog</a>
                </div>
            </nav>

            <div class="space-y-5">
                <h2 class="text-body-lg font-body-md font-semibold text-on-tertiary-container dark:text-on-tertiary">Contact Us</h2>
                <div class="space-y-3">
                    <a class="flex items-center gap-3 text-sm font-body-md text-on-tertiary-container/80 transition-colors hover:text-primary dark:text-on-tertiary/80 dark:hover:text-primary-fixed-dim" href="tel:+959999999999">
                        <span class="material-symbols-outlined text-[18px]" data-icon="call">call</span>
                        <span>+95 999 999 999</span>
                    </a>
                    <a class="flex items-center gap-3 text-sm font-body-md text-on-tertiary-container/80 transition-colors hover:text-primary dark:text-on-tertiary/80 dark:hover:text-primary-fixed-dim" href="mailto:hello@luvshop.com">
                        <span class="material-symbols-outlined text-[18px]" data-icon="mail">mail</span>
                        <span>hello@luvshop.com</span>
                    </a>
                </div>
                <div class="flex items-center gap-3 pt-3">
                    <a class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-label-md font-label-md font-bold text-on-primary transition-opacity hover:opacity-90 dark:bg-primary-fixed-dim dark:text-on-primary-fixed" href="#" aria-label="Facebook">f</a>
                    <a class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-label-sm font-label-sm font-bold text-on-primary transition-opacity hover:opacity-90 dark:bg-primary-fixed-dim dark:text-on-primary-fixed" href="#" aria-label="Google Plus">G+</a>
                    <a class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-label-md font-label-md font-bold text-on-primary transition-opacity hover:opacity-90 dark:bg-primary-fixed-dim dark:text-on-primary-fixed" href="#" aria-label="X">X</a>
                    <a class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-on-primary transition-opacity hover:opacity-90 dark:bg-primary-fixed-dim dark:text-on-primary-fixed" href="#" aria-label="Instagram">
                        <span class="material-symbols-outlined text-[20px]" data-icon="photo_camera">photo_camera</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-12 border-t border-on-tertiary-container/35 pt-4 dark:border-on-tertiary/35">
            <div class="grid items-center gap-4 text-center md:grid-cols-3">
                <span class="hidden md:block"></span>
                <p class="text-label-sm font-label-sm text-on-tertiary-container/70 dark:text-on-tertiary/70">&copy; {{ date('Y') }} LuvShop. All Right reserved</p>
                <nav class="flex flex-wrap justify-center gap-5 md:justify-end" aria-label="Footer legal links">
                    <a class="text-label-sm font-label-sm text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="#">FAQ</a>
                    <a class="text-label-sm font-label-sm text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="#">Privacy</a>
                    <a class="text-label-sm font-label-sm text-on-tertiary-container/75 transition-colors hover:text-primary dark:text-on-tertiary/75 dark:hover:text-primary-fixed-dim" href="#">Terms &amp; Condition</a>
                </nav>
            </div>
        </div>
    </div>
</footer>
