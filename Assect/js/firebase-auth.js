(function () {
    var firebaseConfig = window.__LUVSHOP_FIREBASE_CONFIG__;
    var authLink = document.getElementById('auth-link');

    if (!firebaseConfig || !authLink || typeof firebase === 'undefined') {
        return;
    }

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    var auth = firebase.auth();

    function setLoggedOutState() {
        authLink.textContent = 'Login';
        authLink.href = authLink.dataset.loginHref || '../Auth/login.php';
        authLink.removeAttribute('data-auth-action');
        authLink.removeAttribute('title');
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
    }

    auth.onAuthStateChanged(function (user) {
        if (user) {
            setLoggedInState(user);
            return;
        }

        setLoggedOutState();
    });

    authLink.addEventListener('click', function (event) {
        if (authLink.getAttribute('data-auth-action') !== 'logout') {
            return;
        }

        event.preventDefault();

        auth.signOut().catch(function (error) {
            console.error('Firebase sign-out failed:', error);
            alert('Could not sign out right now. Please try again.');
        });
    });
})();
