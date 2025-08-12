// Add click feedback effect for feature cards
function triggerClickEffect(card) {
    // Add a temporary class for visual feedback
    card.classList.add("clicked");
    setTimeout(() => {
        card.classList.remove("clicked");
    }, 200);
}