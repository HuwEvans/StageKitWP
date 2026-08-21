/**
 * Venues Block v4.0 - Interactive functionality
 * Handles accordion toggling and search filtering
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all venue blocks on the page
    const venueBlocks = document.querySelectorAll('.tm-venues-block');

    venueBlocks.forEach((block) => {
        initVenueBlock(block);
    });
});

/**
 * Initialize a single venue block
 */
function initVenueBlock(block) {
    const searchInput = block.querySelector('.tm-venues-search-input');
    const accordionItems = block.querySelectorAll('.tm-venue-accordion-item');

    // Setup accordion functionality
    accordionItems.forEach((item) => {
        const header = item.querySelector('.tm-venue-accordion-header');
        const content = item.querySelector('.tm-venue-accordion-content');

        header.addEventListener('click', function () {
            toggleAccordion(item, header, content, block);
        });
    });

    // Setup search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            filterVenues(block);
        });
    }
}

/**
 * Toggle accordion item open/closed
 */
function toggleAccordion(item, header, content, block) {
    const isExpanded = header.getAttribute('aria-expanded') === 'true';

    // Close all other items in this block
    block.querySelectorAll('.tm-venue-accordion-header').forEach((h) => {
        if (h !== header) {
            h.setAttribute('aria-expanded', 'false');
            h.parentElement.querySelector('.tm-venue-accordion-content').hidden = true;
            h.classList.remove('active');
        }
    });

    // Toggle current item
    if (isExpanded) {
        header.setAttribute('aria-expanded', 'false');
        content.hidden = true;
        header.classList.remove('active');
    } else {
        header.setAttribute('aria-expanded', 'true');
        content.hidden = false;
        header.classList.add('active');

        // Trigger map resize if Leaflet is available
        setTimeout(() => {
            const mapElement = content.querySelector('[id^="tm-venue-map-"]');
            if (mapElement && window.L && window.L.mapInstances) {
                const map = window.L.mapInstances[mapElement.id];
                if (map) {
                    map.invalidateSize();
                }
            }
        }, 300);
    }
}

/**
 * Filter venues based on search input
 */
function filterVenues(block) {
    const searchInput = block.querySelector('.tm-venues-search-input');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const accordionItems = block.querySelectorAll('.tm-venue-accordion-item');
    let visibleCount = 0;

    accordionItems.forEach((item) => {
        const venueName = item.getAttribute('data-venue-name').toLowerCase();
        const isMatch = venueName.includes(searchTerm);

        if (isMatch) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Show/hide "no results" message
    let noResultsMessage = block.querySelector('.tm-venues-no-results');
    if (visibleCount === 0 && searchTerm) {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.className = 'tm-venues-no-results';
            noResultsMessage.textContent = 'No venues match your search.';
            block.querySelector('.tm-venues-accordion').appendChild(noResultsMessage);
        }
        noResultsMessage.style.display = '';
    } else if (noResultsMessage) {
        noResultsMessage.style.display = 'none';
    }
}
