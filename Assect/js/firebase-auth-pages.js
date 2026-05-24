(function () {
    var firebaseConfig = window.__LUVSHOP_FIREBASE_CONFIG__;

    if (!firebaseConfig || typeof firebase === 'undefined') {
        return;
    }

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    var auth = firebase.auth();
    var defaultRedirectUrl = '../Users/Home.php';
    var syncEndpoint = 'sync_user.php';
    var isAuthSyncInProgress = false;

    function resolveRedirectUrl() {
        var params = new URLSearchParams(window.location.search);
        var redirect = params.get('redirect') || '';
        if (!redirect) {
            return defaultRedirectUrl;
        }

        try {
            var target = new URL(redirect, window.location.href);
            if (target.origin !== window.location.origin) {
                return defaultRedirectUrl;
            }
            if (/\/Auth\/(?:login|register)\.php$/i.test(target.pathname)) {
                return defaultRedirectUrl;
            }
            return target.href;
        } catch (_error) {
            return defaultRedirectUrl;
        }
    }

    var redirectUrl = resolveRedirectUrl();

    function setFeedback(element, message, isError) {
        if (!element) {
            return;
        }

        element.textContent = message || '';
        element.classList.remove('text-error', 'text-secondary');

        if (message) {
            element.classList.add(isError ? 'text-error' : 'text-secondary');
        }
    }

    function getFriendlyAuthErrorMessage(error) {
        if (!error || !error.code) {
            return 'Authentication failed. Please try again.';
        }

        if (error.code === 'auth/operation-not-allowed') {
            return 'Google sign-in is disabled. Enable Google in Firebase Authentication > Sign-in method.';
        }

        if (error.code === 'auth/unauthorized-domain') {
            return 'This domain is not authorized. Add localhost in Firebase Authentication > Settings > Authorized domains.';
        }

        if (error.code === 'auth/popup-blocked') {
            return 'Popup was blocked. Allow popups for this site and try again.';
        }

        if (error.code === 'auth/popup-closed-by-user') {
            return 'Popup was closed before sign-in completed.';
        }

        return error.message || 'Authentication failed. Please try again.';
    }

    function syncUserToMySql(user) {
        if (window.LuvShopAuthSession && typeof window.LuvShopAuthSession.syncUser === 'function') {
            return window.LuvShopAuthSession.syncUser(user, { forceRefreshToken: true });
        }

        return user.getIdToken(true).then(function (idToken) {
            var providerId = '';
            if (Array.isArray(user.providerData) && user.providerData.length > 0) {
                providerId = user.providerData[0].providerId || '';
            }

            return fetch(syncEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idToken: idToken,
                    uid: user.uid || '',
                    email: user.email || '',
                    displayName: user.displayName || '',
                    providerId: providerId
                })
            });
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data || !data.ok) {
                    var message = (data && data.message) ? data.message : 'Failed to sync user to MySQL.';
                    throw new Error(message);
                }

                return data;
            });
        });
    }

    var loginForm = document.getElementById('firebase-login-form');
    var loginEmail = document.getElementById('firebase-login-email');
    var loginPassword = document.getElementById('firebase-login-password');
    var loginFeedback = document.getElementById('firebase-login-feedback');

    if (loginForm && loginEmail && loginPassword) {
        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var email = loginEmail.value.trim();
            var password = loginPassword.value;

            if (!email || !password) {
                setFeedback(loginFeedback, 'Please enter both email and password.', true);
                return;
            }

            setFeedback(loginFeedback, 'Signing in...', false);

            auth.signInWithEmailAndPassword(email, password).catch(function (error) {
                setFeedback(loginFeedback, error.message, true);
            });
        });
    }

    var registerForm = document.getElementById('firebase-register-form');
    var registerName = document.getElementById('firebase-register-name');
    var registerEmail = document.getElementById('firebase-register-email');
    var registerPassword = document.getElementById('firebase-register-password');
    var registerFeedback = document.getElementById('firebase-register-feedback');

    function signInWithGoogle(feedbackElement) {
        var provider = new firebase.auth.GoogleAuthProvider();
        provider.setCustomParameters({ prompt: 'select_account' });

        setFeedback(feedbackElement, 'Opening Google sign-in...', false);

        auth.signInWithPopup(provider).catch(function (error) {
            setFeedback(feedbackElement, getFriendlyAuthErrorMessage(error), true);
        });
    }

    var googleAuthButtons = document.querySelectorAll('[data-google-auth]');

    if (googleAuthButtons.length > 0) {
        googleAuthButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var mode = button.getAttribute('data-google-auth');
                var feedbackElement = mode === 'register' ? registerFeedback : loginFeedback;

                signInWithGoogle(feedbackElement);
            });
        });
    }

    if (registerForm && registerName && registerEmail && registerPassword) {
        registerForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var displayName = registerName.value.trim();
            var email = registerEmail.value.trim();
            var password = registerPassword.value;

            if (!displayName || !email || !password) {
                setFeedback(registerFeedback, 'Please complete all fields.', true);
                return;
            }

            setFeedback(registerFeedback, 'Creating your account...', false);

            auth.createUserWithEmailAndPassword(email, password)
                .then(function (credential) {
                    if (credential.user && displayName) {
                        return credential.user.updateProfile({ displayName: displayName });
                    }
                })
                .catch(function (error) {
                    setFeedback(registerFeedback, error.message, true);
                });
        });
    }

    auth.onAuthStateChanged(function (user) {
        if (!user) {
            isAuthSyncInProgress = false;
            return;
        }

        if (isAuthSyncInProgress) {
            return;
        }

        isAuthSyncInProgress = true;

        var feedbackElement = registerForm ? registerFeedback : loginFeedback;
        setFeedback(feedbackElement, 'Saving your account to MySQL...', false);

        syncUserToMySql(user)
            .then(function () {
                window.location.href = redirectUrl;
            })
            .catch(function (error) {
                isAuthSyncInProgress = false;
                setFeedback(feedbackElement, error.message || 'Failed to save account data to MySQL.', true);
            });
    });
})();
