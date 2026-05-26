(function () {
    var firebaseConfig = window.__LUVSHOP_FIREBASE_CONFIG__;
    var authLink = document.getElementById('auth-link');
    var profileLink = document.getElementById('profile-link');
    var profileAvatarImage = document.getElementById('profile-avatar-image');
    var profileAvatarIcon = document.getElementById('profile-avatar-icon');
    var syncEndpoint = '../Auth/sync_user.php';
    var sessionSyncPromise = null;
    var sessionSyncUid = '';
    var sessionSyncedUid = '';
    var sessionClearPromise = null;

    if (!firebaseConfig || !authLink || typeof firebase === 'undefined') {
        return;
    }

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    var auth = firebase.auth();

    function syncUserToPhpSession(user, options) {
        options = options || {};

        if (!user) {
            return Promise.reject(new Error('No signed-in user to sync.'));
        }

        if (sessionSyncPromise && sessionSyncUid === user.uid && options.forceRefreshToken !== true) {
            return sessionSyncPromise;
        }

        sessionSyncUid = user.uid || '';
        sessionSyncPromise = user.getIdToken(options.forceRefreshToken === true).then(function (idToken) {
            var providerId = '';
            if (Array.isArray(user.providerData) && user.providerData.length > 0 && user.providerData[0]) {
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
                    var message = data && data.message ? data.message : 'Could not sync user session.';
                    throw new Error(message);
                }

                sessionSyncedUid = user.uid || '';
                return data;
            });
        }).catch(function (error) {
            sessionSyncPromise = null;
            sessionSyncUid = '';
            sessionSyncedUid = '';
            throw error;
        });

        return sessionSyncPromise;
    }

    function clearPhpSession() {
        sessionSyncedUid = '';
        sessionSyncUid = '';
        sessionSyncPromise = null;

        if (sessionClearPromise) {
            return sessionClearPromise;
        }

        sessionClearPromise = fetch(syncEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        }).catch(function (error) {
            console.error('PHP session clear failed:', error);
        }).finally(function () {
            sessionClearPromise = null;
        });

        return sessionClearPromise;
    }

    window.LuvShopAuthSession = {
        syncUser: syncUserToPhpSession,
        clear: clearPhpSession
    };

    function setLoggedOutState() {
        authLink.textContent = 'Login';
        authLink.href = authLink.dataset.loginHref || '../Auth/login.php';
        authLink.removeAttribute('data-auth-action');
        authLink.removeAttribute('title');

        if (profileLink) {
            profileLink.removeAttribute('title');
        }
        if (profileAvatarImage) {
            profileAvatarImage.classList.add('hidden');
            profileAvatarImage.removeAttribute('src');
        }
        if (profileAvatarIcon) {
            profileAvatarIcon.classList.remove('hidden');
        }
    }

    function setLoggedInState(user) {
        authLink.textContent = 'Logout';
        authLink.href = '#';
        authLink.setAttribute('data-auth-action', 'logout');

        if (user && user.email) {
            authLink.title = 'Signed in as ' + user.email;
        } else {
            authLink.removeAttribute('title');
        }

        if (profileLink) {
            var profileTitle = 'Profile';
            if (user && user.email) {
                profileTitle = user.email;
            } else if (user && user.displayName) {
                profileTitle = user.displayName;
            }
            profileLink.title = profileTitle;
        }

        if (profileAvatarImage && profileAvatarIcon) {
            if (user && user.photoURL) {
                profileAvatarImage.src = user.photoURL;
                profileAvatarImage.classList.remove('hidden');
                profileAvatarIcon.classList.add('hidden');
            } else {
                profileAvatarImage.classList.add('hidden');
                profileAvatarImage.removeAttribute('src');
                profileAvatarIcon.classList.remove('hidden');
            }
        }
    }

    auth.onAuthStateChanged(function (user) {
        if (user) {
            setLoggedInState(user);
            syncUserToPhpSession(user).catch(function (error) {
                console.error('LuvShop session sync failed:', error);
            });
            return;
        }

        setLoggedOutState();
        clearPhpSession();
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        var checkoutLink = target.closest('a[data-checkout-link]');
        if (!checkoutLink || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var user = auth.currentUser;
        if (!user) {
            return;
        }

        event.preventDefault();
        checkoutLink.setAttribute('aria-busy', 'true');
        checkoutLink.classList.add('opacity-70', 'pointer-events-none');

        var redirected = false;
        var goToCheckout = function () {
            if (redirected) {
                return;
            }
            redirected = true;
            window.location.href = checkoutLink.href;
        };
        var fallbackTimer = window.setTimeout(goToCheckout, 1200);

        syncUserToPhpSession(user).then(function () {
            window.clearTimeout(fallbackTimer);
            goToCheckout();
        }).catch(function (error) {
            console.error('Checkout session sync failed:', error);
            window.clearTimeout(fallbackTimer);
            goToCheckout();
        });
    });

    authLink.addEventListener('click', function (event) {
        if (authLink.getAttribute('data-auth-action') !== 'logout') {
            return;
        }

        event.preventDefault();

        auth.signOut().then(function () {
            return clearPhpSession();
        }).catch(function (error) {
            console.error('Firebase sign-out failed:', error);
            alert('Could not sign out right now. Please try again.');
        });
    });
})();
