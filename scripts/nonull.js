document.addEventListener('DOMContentLoaded', () => {
    const fireflies = document.querySelectorAll('.firefly');
    const blackHoles = document.querySelectorAll('.black-hole');

    function updateFireflies() {
        fireflies.forEach(firefly => {
            blackHoles.forEach(blackHole => {
                const dx = blackHole.offsetLeft - firefly.offsetLeft;
                const dy = blackHole.offsetTop - firefly.offsetTop;
                const distance = Math.sqrt(dx * dx + dy * dy);
                const minDistance = blackHole.offsetWidth / 2;

                if (distance < minDistance) {
                    firefly.classList.add('firefly-to-blackhole');
                    firefly.style.opacity = '0';
                } else {
                    firefly.classList.remove('firefly-to-blackhole');
                    firefly.style.opacity = '';
                }

                blackHoles.forEach(otherBlackHole => {
                    if (blackHole !== otherBlackHole) {
                        const dx2 = otherBlackHole.offsetLeft - blackHole.offsetLeft;
                        const dy2 = otherBlackHole.offsetTop - blackHole.offsetTop;
                        const distance2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
                        const minDistance2 = (blackHole.offsetWidth + otherBlackHole.offsetWidth) / 2;

                        if (distance2 < minDistance2) {
                            blackHole.style.transform = `scale(${parseFloat(blackHole.style.transform.replace('scale(', '').replace(')', '')) + 0.2})`;
                            otherBlackHole.remove();
                        }
                    }
                });
            });
        });
    }

    setInterval(updateFireflies, 100);
});