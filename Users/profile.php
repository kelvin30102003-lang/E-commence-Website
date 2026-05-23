<?php

declare(strict_types=1);

$activePage = 'profile';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Profile | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
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
                }
            }
        }
    </script>
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden">
<?php require_once __DIR__ . '/../Templates/header.php'; ?>

<main class="pt-24 pb-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto min-h-screen" id="app-main">
    <section class="mb-lg">
        <h1 class="text-headline-lg font-headline-lg text-primary">My Profile</h1>
        <p class="text-on-surface-variant">Update your account details and password.</p>
    </section>

    <section class="hidden bg-surface-container-lowest rounded-2xl p-xl soft-shadow border border-outline-variant/20" id="profile-login-required">
        <h2 class="text-headline-md font-headline-md text-primary mb-sm">Please log in first</h2>
        <p class="text-on-surface-variant mb-md">You need to login before editing your profile.</p>
        <a class="inline-flex px-lg py-sm rounded-full bg-primary text-on-primary font-label-md" href="../Auth/login.php">Go to Login</a>
    </section>

    <section class="hidden grid grid-cols-1 lg:grid-cols-12 gap-lg" id="profile-content">
        <aside class="lg:col-span-4">
            <div class="bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20 sticky top-24">
                <div class="flex items-center gap-md">
                    <div class="w-16 h-16 rounded-full overflow-hidden border border-outline-variant/40 bg-surface-container-low flex items-center justify-center text-primary">
                        <img alt="Profile Avatar" class="hidden w-full h-full object-cover" id="profile-avatar-img" src=""/>
                        <span class="material-symbols-outlined text-3xl" id="profile-avatar-fallback">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-on-surface truncate" id="profile-name-label">User</p>
                        <p class="text-sm text-on-surface-variant truncate" id="profile-email-label">-</p>
                    </div>
                </div>
                <div class="mt-md pt-md border-t border-outline-variant/30 text-sm text-on-surface-variant">
                    <p>Account managed with Firebase.</p>
                </div>
            </div>
        </aside>

        <div class="lg:col-span-8 space-y-lg">
            <section class="bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20">
                <h2 class="text-headline-md font-headline-md text-primary mb-md">Account Details</h2>
                <p class="hidden mb-md rounded-xl px-md py-sm text-sm" id="profile-account-feedback"></p>

                <form class="space-y-md" id="profile-account-form">
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="profile-name-input">Name</label>
                        <input class="w-full rounded-xl border-outline-variant" id="profile-name-input" maxlength="150" type="text"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="profile-email-input">Email</label>
                        <input class="w-full rounded-xl border-outline-variant" id="profile-email-input" maxlength="255" type="email"/>
                    </div>
                    <button class="px-lg py-sm rounded-full bg-primary text-on-primary font-semibold hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" id="profile-account-submit" type="submit">
                        Save Changes
                    </button>
                </form>
            </section>

            <section class="bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20">
                <h2 class="text-headline-md font-headline-md text-primary mb-md">Change Password</h2>
                <p class="text-sm text-on-surface-variant mb-sm">Current password is required to set a new password.</p>
                <p class="hidden mb-md rounded-xl px-md py-sm text-sm" id="profile-password-feedback"></p>

                <form class="space-y-md" id="profile-password-form">
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="profile-current-password">Current Password</label>
                        <input class="w-full rounded-xl border-outline-variant" id="profile-current-password" minlength="6" required type="password"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="profile-new-password">New Password</label>
                        <input class="w-full rounded-xl border-outline-variant" id="profile-new-password" minlength="6" required type="password"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="profile-confirm-password">Confirm New Password</label>
                        <input class="w-full rounded-xl border-outline-variant" id="profile-confirm-password" minlength="6" required type="password"/>
                    </div>
                    <button class="px-lg py-sm rounded-full bg-primary text-on-primary font-semibold hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" id="profile-password-submit" type="submit">
                        Change Password
                    </button>
                </form>
            </section>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../Templates/footer.php'; ?>

<script>
(() => {
    const loginRequiredCard = document.getElementById('profile-login-required');
    const profileContent = document.getElementById('profile-content');

    const nameInput = document.getElementById('profile-name-input');
    const emailInput = document.getElementById('profile-email-input');
    const accountForm = document.getElementById('profile-account-form');
    const accountSubmit = document.getElementById('profile-account-submit');
    const accountFeedback = document.getElementById('profile-account-feedback');

    const passwordForm = document.getElementById('profile-password-form');
    const passwordSubmit = document.getElementById('profile-password-submit');
    const currentPasswordInput = document.getElementById('profile-current-password');
    const newPasswordInput = document.getElementById('profile-new-password');
    const confirmPasswordInput = document.getElementById('profile-confirm-password');
    const passwordFeedback = document.getElementById('profile-password-feedback');

    const avatarImage = document.getElementById('profile-avatar-img');
    const avatarFallback = document.getElementById('profile-avatar-fallback');
    const nameLabel = document.getElementById('profile-name-label');
    const emailLabel = document.getElementById('profile-email-label');

    const showLoginRequired = () => {
        if (loginRequiredCard) {
            loginRequiredCard.classList.remove('hidden');
        }
        if (profileContent) {
            profileContent.classList.add('hidden');
        }
    };

    const showProfileContent = () => {
        if (loginRequiredCard) {
            loginRequiredCard.classList.add('hidden');
        }
        if (profileContent) {
            profileContent.classList.remove('hidden');
        }
    };

    const setFeedback = (target, message, isError) => {
        if (!target) {
            return;
        }
        target.textContent = message || '';
        target.classList.remove('hidden', 'bg-error-container', 'text-on-error-container', 'bg-secondary-container', 'text-on-secondary-container');
        if (!message) {
            target.classList.add('hidden');
            return;
        }
        if (isError) {
            target.classList.add('bg-error-container', 'text-on-error-container');
        } else {
            target.classList.add('bg-secondary-container', 'text-on-secondary-container');
        }
    };

    const providerHasPassword = (user) => {
        if (!user || !Array.isArray(user.providerData)) {
            return false;
        }
        return user.providerData.some((provider) => provider && provider.providerId === 'password');
    };

    const mapAuthError = (error) => {
        if (!error || !error.code) {
            return 'Request failed. Please try again.';
        }
        switch (error.code) {
            case 'auth/wrong-password':
                return 'Current password is incorrect.';
            case 'auth/weak-password':
                return 'New password is too weak. Use at least 6 characters.';
            case 'auth/requires-recent-login':
                return 'For security, please log out and log in again, then retry.';
            case 'auth/email-already-in-use':
                return 'This email is already used by another account.';
            case 'auth/invalid-email':
                return 'Please enter a valid email address.';
            default:
                return error.message || 'Request failed. Please try again.';
        }
    };

    const syncUserToMySql = async (user) => {
        const idToken = await user.getIdToken(true);
        const providerId = Array.isArray(user.providerData) && user.providerData[0] ? (user.providerData[0].providerId || '') : '';

        const response = await fetch('../Auth/sync_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idToken,
                uid: user.uid || '',
                email: user.email || '',
                displayName: user.displayName || '',
                providerId
            })
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.ok) {
            throw new Error((payload && payload.message) ? payload.message : 'Failed to sync user to MySQL.');
        }
    };

    const fillProfile = (user) => {
        const displayName = (user && user.displayName ? user.displayName : '').trim();
        const email = (user && user.email ? user.email : '').trim();

        if (nameInput) {
            nameInput.value = displayName;
        }
        if (emailInput) {
            emailInput.value = email;
        }
        if (nameLabel) {
            nameLabel.textContent = displayName !== '' ? displayName : 'User';
        }
        if (emailLabel) {
            emailLabel.textContent = email !== '' ? email : '-';
        }

        if (avatarImage && avatarFallback) {
            if (user && user.photoURL) {
                avatarImage.src = user.photoURL;
                avatarImage.classList.remove('hidden');
                avatarFallback.classList.add('hidden');
            } else {
                avatarImage.classList.add('hidden');
                avatarImage.removeAttribute('src');
                avatarFallback.classList.remove('hidden');
            }
        }
    };

    let isInitialized = false;
    const initProfileLogic = (auth) => {
        if (isInitialized) {
            return;
        }
        isInitialized = true;

        auth.onAuthStateChanged(async (user) => {
            if (!user) {
                showLoginRequired();
                return;
            }

            showProfileContent();
            fillProfile(user);

            try {
                await syncUserToMySql(user);
            } catch (error) {
                console.error(error);
            }
        });

        if (accountForm) {
            accountForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                setFeedback(accountFeedback, '', false);

                const user = auth.currentUser;
                if (!user) {
                    setFeedback(accountFeedback, 'Please log in first.', true);
                    return;
                }

                const nextName = nameInput ? nameInput.value.trim() : '';
                const nextEmail = emailInput ? emailInput.value.trim() : '';

                if (nextEmail === '') {
                    setFeedback(accountFeedback, 'Email is required.', true);
                    return;
                }

                if (accountSubmit) {
                    accountSubmit.disabled = true;
                }

                try {
                    const jobs = [];

                    const currentName = (user.displayName || '').trim();
                    const currentEmail = (user.email || '').trim();

                    if (nextName !== currentName) {
                        jobs.push(user.updateProfile({ displayName: nextName }));
                    }
                    if (nextEmail !== currentEmail) {
                        jobs.push(user.updateEmail(nextEmail));
                    }

                    if (jobs.length > 0) {
                        await Promise.all(jobs);
                        await syncUserToMySql(user);
                    }

                    fillProfile(user);
                    setFeedback(accountFeedback, 'Profile updated successfully.', false);
                } catch (error) {
                    setFeedback(accountFeedback, mapAuthError(error), true);
                } finally {
                    if (accountSubmit) {
                        accountSubmit.disabled = false;
                    }
                }
            });
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                setFeedback(passwordFeedback, '', false);

                const user = auth.currentUser;
                if (!user) {
                    setFeedback(passwordFeedback, 'Please log in first.', true);
                    return;
                }

                if (!providerHasPassword(user)) {
                    setFeedback(passwordFeedback, 'Password change is available only for email/password accounts.', true);
                    return;
                }

                const currentPassword = currentPasswordInput ? currentPasswordInput.value : '';
                const newPassword = newPasswordInput ? newPasswordInput.value : '';
                const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

                if (currentPassword === '' || newPassword === '' || confirmPassword === '') {
                    setFeedback(passwordFeedback, 'All password fields are required.', true);
                    return;
                }

                if (newPassword.length < 6) {
                    setFeedback(passwordFeedback, 'New password must be at least 6 characters.', true);
                    return;
                }

                if (newPassword !== confirmPassword) {
                    setFeedback(passwordFeedback, 'Password confirmation does not match.', true);
                    return;
                }

                if (!user.email) {
                    setFeedback(passwordFeedback, 'Current account email is missing.', true);
                    return;
                }

                if (passwordSubmit) {
                    passwordSubmit.disabled = true;
                }

                try {
                    const credential = firebase.auth.EmailAuthProvider.credential(user.email, currentPassword);
                    await user.reauthenticateWithCredential(credential);
                    await user.updatePassword(newPassword);

                    if (passwordForm) {
                        passwordForm.reset();
                    }
                    setFeedback(passwordFeedback, 'Password changed successfully.', false);
                } catch (error) {
                    setFeedback(passwordFeedback, mapAuthError(error), true);
                } finally {
                    if (passwordSubmit) {
                        passwordSubmit.disabled = false;
                    }
                }
            });
        }
    };

    const tryInitFirebase = (attempt = 0) => {
        const firebaseConfig = window.__LUVSHOP_FIREBASE_CONFIG__;
        const hasFirebaseRuntime = typeof firebase !== 'undefined' && firebase && typeof firebase.auth === 'function';

        if (!firebaseConfig || !hasFirebaseRuntime) {
            if (attempt < 100) {
                window.setTimeout(() => tryInitFirebase(attempt + 1), 100);
                return;
            }
            showLoginRequired();
            return;
        }

        if (!firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }

        const auth = firebase.auth();
        initProfileLogic(auth);
    };

    tryInitFirebase();
})();
</script>
</body>
</html>
