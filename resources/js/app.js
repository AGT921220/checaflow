import './features/branches/map.js';
import './features/attendance/punch.js';
import './features/ui/modal.js';

const clock = document.querySelector('[data-live-clock]');

if (clock instanceof HTMLElement) {
    const zone = clock.dataset.timezone || 'UTC';
    const render = () => {
        clock.textContent = new Intl.DateTimeFormat('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
            timeZone: zone,
        }).format(new Date());
    };

    render();
    window.setInterval(render, 1000);
}
