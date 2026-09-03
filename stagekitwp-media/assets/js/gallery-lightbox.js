(() => {
    'use strict';

    let activeGallery = [];
    let activeIndex = 0;
    let lastTrigger = null;

    const lightbox = document.createElement('div');
    lightbox.className = 'skwpm-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Image viewer');
    lightbox.innerHTML = `
        <div class="skwpm-lightbox-dialog">
            <button class="skwpm-lightbox-button skwpm-lightbox-previous" type="button" aria-label="Previous image">&#8249;</button>
            <div class="skwpm-lightbox-image-wrap">
                <img class="skwpm-lightbox-image" alt="">
                <p class="skwpm-lightbox-caption" hidden></p>
            </div>
            <button class="skwpm-lightbox-button skwpm-lightbox-next" type="button" aria-label="Next image">&#8250;</button>
            <button class="skwpm-lightbox-button skwpm-lightbox-close" type="button" aria-label="Close image viewer">&times;</button>
        </div>`;

    const image = lightbox.querySelector('.skwpm-lightbox-image');
    const caption = lightbox.querySelector('.skwpm-lightbox-caption');
    const previous = lightbox.querySelector('.skwpm-lightbox-previous');
    const next = lightbox.querySelector('.skwpm-lightbox-next');
    const close = lightbox.querySelector('.skwpm-lightbox-close');

    function setImage(index) {
        activeIndex = index;
        const trigger = activeGallery[activeIndex];
        image.src = trigger.dataset.skwpmLightboxSrc;
        image.alt = trigger.dataset.skwpmLightboxAlt || '';
        caption.textContent = trigger.dataset.skwpmLightboxCaption || trigger.dataset.skwpmLightboxAlt || '';
        caption.hidden = !caption.textContent;
        previous.disabled = activeGallery.length < 2;
        next.disabled = activeGallery.length < 2;
    }

    function open(trigger) {
        const gallery = trigger.closest('.skwpm-gallery');
        activeGallery = Array.from(gallery.querySelectorAll('.skwpm-lightbox-trigger'));
        lastTrigger = trigger;
        setImage(activeGallery.indexOf(trigger));
        lightbox.classList.add('is-open');
        document.body.classList.add('skwpm-lightbox-open');
        close.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        document.body.classList.remove('skwpm-lightbox-open');
        image.removeAttribute('src');
        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    function move(direction) {
        if (activeGallery.length < 2) {
            return;
        }
        setImage((activeIndex + direction + activeGallery.length) % activeGallery.length);
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.skwpm-lightbox-trigger');
        if (trigger) {
            event.preventDefault();
            open(trigger);
        }
    });

    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    close.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (!lightbox.classList.contains('is-open')) {
            return;
        }
        if (event.key === 'Escape') {
            closeLightbox();
        } else if (event.key === 'ArrowLeft') {
            move(-1);
        } else if (event.key === 'ArrowRight') {
            move(1);
        }
    });

    document.body.appendChild(lightbox);
})();
