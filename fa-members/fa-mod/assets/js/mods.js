document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const id = urlParams.get('id');

    const myProfileLi = document.querySelector('li.menu-item-313'); // <li> for My Profile
    const myProfileLink = myProfileLi ? myProfileLi.querySelector('a') : null; // <a> inside

    if ((view === 'my-profile' || view === 'edit') && myProfileLi && myProfileLink) {
        // Find currently active <a> and remove elementor-item-active
        const activeLink = document.querySelector('a.elementor-item-active');
        if (activeLink) {
            activeLink.classList.remove('elementor-item-active');
        }

        // Add active class to My Profile <a>
        myProfileLink.classList.add('elementor-item-active');

        // Also ensure <li> has current-menu-item class (optional)
        myProfileLi.classList.add('current-menu-item');
    }
});

