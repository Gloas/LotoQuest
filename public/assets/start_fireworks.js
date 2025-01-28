const fireworks = new FireworksOverlay({
    colors: ['#FF5733', '#FFC300', '#DAF7A6', '#C70039', '#900C3F', '#581845'],
    // the number of particles
    particleCount: 350,
    // simulated gravity affecting the particles
    gravity: 0.01,
    // animation speed
    speed: { min: 2, max: 8 },
    // radius range of particles
    radius: { min: 1, max: 6 },
    // animation interval in ms
    interval: 800,
    // CSS z-index
    zIndex: 9999,
    // the selector of the trigger button
    toggleButton: null,
});

function startFireworks(element)
{
    fireworks.startAnimation();
}


function stopFireworks(element)
{
    fireworks.stopAnimation();
}
