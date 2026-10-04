(() => {
    'use strict';

    let activePanel = null;
    let activeTrigger = null;

    function closePopover() {
        if (activePanel) {
            activePanel.hidden = true;
        }
        if (activeTrigger) {
            activeTrigger.setAttribute('aria-expanded', 'false');
        }
        activePanel = null;
        activeTrigger = null;
    }

    function positionPopover(trigger, panel) {
        const rect = trigger.getBoundingClientRect();
        const padding = 8;
        const width = panel.offsetWidth;
        const height = panel.offsetHeight;
        const left = Math.max(padding, Math.min(rect.left, window.innerWidth - width - padding));
        const spaceBelow = window.innerHeight - rect.bottom;
        const top = spaceBelow < height + padding && rect.top > spaceBelow
            ? rect.top - height - padding : rect.bottom + padding;

        panel.style.left = `${left}px`;
        panel.style.top = `${Math.max(padding, Math.min(top, window.innerHeight - height - padding))}px`;
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.aam-ap-trigger');

        if (trigger) {
            event.preventDefault();
            const panel = document.getElementById(trigger.getAttribute('aria-controls'));
            if (!panel) {
                return;
            }
            if (activePanel === panel) {
                closePopover();
                return;
            }
            closePopover();
            activePanel = panel;
            activeTrigger = trigger;
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            positionPopover(trigger, panel);
            return;
        }

        if (!activePanel || !activePanel.contains(event.target)) {
            closePopover();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activePanel) {
            const trigger = activeTrigger;
            closePopover();
            trigger.focus();
        }
    });

    window.addEventListener('resize', closePopover);
    document.addEventListener('scroll', (event) => {
        if (activePanel && !activePanel.contains(event.target)) {
            closePopover();
        }
    }, true);
})();
