/* public/js/viewer360.js */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('viewer-360-modal');
    const closeBtn = document.querySelector('.close-viewer');
    let viewer = null;

    // Use event delegation for buttons with class 'btn-360-trigger'
    document.addEventListener('click', function (event) {
        const btn360 = event.target.closest('.btn-360-trigger');

        if (btn360) {
            const imageUrl = btn360.getAttribute('data-image');
            const streetViewUrl = btn360.getAttribute('data-streetview');

            if (!imageUrl && !streetViewUrl) return;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Disable scroll

            const container = document.getElementById('panorama');
            container.innerHTML = ''; // Clear container

            if (streetViewUrl) {
                // Handle Google Street View (embed iframe)
                container.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0" src="${streetViewUrl}" allowfullscreen></iframe>`;
            } else if (imageUrl) {
                // Initialize Pannellum for equirectangular image
                if (viewer) {
                    viewer.destroy();
                }

                viewer = pannellum.viewer('panorama', {
                    "type": "equirectangular",
                    "panorama": imageUrl,
                    "autoLoad": true,
                    "showFullscreenCtrl": true,
                    "showZoomCtrl": true,
                    "hfov": 110,
                    "yaw": 0,
                    "pitch": 0,
                    "mouseZoom": true,
                    "keyboardZoom": true,
                    "dragToThrow": 10,
                    "compass": true
                });
            }
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scroll
            if (viewer) {
                viewer.destroy();
                viewer = null;
            }
            document.getElementById('panorama').innerHTML = '';
        });
    }

    // Close on outside click
    window.addEventListener('click', function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (viewer) {
                viewer.destroy();
                viewer = null;
            }
            document.getElementById('panorama').innerHTML = '';
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === "Escape" && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (viewer) {
                viewer.destroy();
                viewer = null;
            }
            document.getElementById('panorama').innerHTML = '';
        }
    });
});
