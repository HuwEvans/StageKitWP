/**
 * Board Members Block v4.0 - Interactive functionality for accordion layout
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all board member blocks with accordion layout
    const boardMemberBlocks = document.querySelectorAll('.tm-board-members-block[data-layout="accordion"]');

    boardMemberBlocks.forEach((block) => {
        initBoardMembersAccordion(block);
    });
});

/**
 * Initialize accordion for a board members block
 */
function initBoardMembersAccordion(block) {
    const accordionItems = block.querySelectorAll('.tm-board-member-accordion-item');

    accordionItems.forEach((item) => {
        const header = item.querySelector('.tm-board-member-accordion-header');
        const content = item.querySelector('.tm-board-member-accordion-content');

        header.addEventListener('click', function () {
            toggleBoardMemberAccordion(item, header, content, block);
        });

        // Keyboard accessibility
        header.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleBoardMemberAccordion(item, header, content, block);
            }
        });
    });
}

/**
 * Toggle board member accordion item
 */
function toggleBoardMemberAccordion(item, header, content, block) {
    const isExpanded = header.getAttribute('aria-expanded') === 'true';

    // Close all other items in this block
    block.querySelectorAll('.tm-board-member-accordion-header').forEach((h) => {
        if (h !== header) {
            h.setAttribute('aria-expanded', 'false');
            h.parentElement.querySelector('.tm-board-member-accordion-content').hidden = true;
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
    }
}
